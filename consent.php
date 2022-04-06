<?php

// Dependencies
require './api/include/config.php';
require './api/include/include.php';

// Start Session
session_start();

//Acceptable Referers
$authorizedReferers = array('https:login.circle.army');
// verify valid referrers
$serverReferal = preg_replace('/\//','',$_SERVER['HTTP_REFERER']);
$matched_ref = 0;
$quote = '';
foreach ($authorizedReferers as $auth_ref) {
    $quote = preg_quote($auth_ref);
    if(preg_match('/'.$quote.'/',$serverReferal)){
        $matched_ref = 1;
        error_log("consent.php: Valid Referer");
    }
}
if($matched_ref == 0){
    $returnArray['message']='quote: '.$quote.'Wrong Referer: '.$serverReferal;
    printf(json_encode($returnArray));
    exit();
}

// Get consent challenge
if(isset($_GET['consent_challenge'])){
  $consentChallenge = $_GET['consent_challenge'];
}
elseif(isset($_POST['consent_challenge'])){
    $consentChallenge = $_POST['consent_challenge'];
}
else{
  error_log("login.php: missing consent_challenge");
  exit();
}
error_log("consent.php: Received consent challenge ".$consentChallenge);

// Get consent challenge information
$url=$OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/consent?consent_challenge=".urlencode($consentChallenge);
// Get challenge information
$crl = curl_init($url);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($crl, CURLINFO_HEADER_OUT, true);
// Set HTTP Header for POST request 
curl_setopt($crl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
));

$result = curl_exec($crl);
// Decode result
$resultObj = json_decode($result);
//var_dump($resultObj);
$client = $resultObj->client;
$scopes = explode(' ',$resultObj->client->scope);
$sub = $resultObj->subject;

if(!isset($resultObj->client->scope)){
    error_log("consent.php: Invalid Challenge");
    exit();
}

// Are we consenting?
if($_POST['consent_accept'] == "true"){
    error_log("consent.php: Accepting");
    
    $url = $OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/consent/accept?consent_challenge=".$consentChallenge;
    $grant_scope = '';
    $i = 0;

    // Check for valid scopes
    foreach($scopes as $scope){
        $target = strval($i).'-scope';
        if(isset($_POST[$target])){
            $grant_scope = $grant_scope.$_POST[$target].' ';
        }
        $i++;
    }
    $grant_scope = trim($grant_scope);
    error_log("consent.php: accepting scopes: ".$grant_scope);

    $postData = array('consent_challenge'=>$consentChallenge,'grant_scope'=>$grant_scope,'sub'=>$sub);
    $postJson = json_encode($postData);

    $apiUrl = "https://login.circle.army/api/consent.php";
    $crl = curl_init($apiUrl);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_POST, true);
    curl_setopt($crl, CURLOPT_POSTFIELDS,$postJson);
    // Set HTTP Header for POST request 
    curl_setopt($crl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
    ));

    // Submit the POST request
    try{
        $result = curl_exec($crl);
        //var_dump($result);
    }
    catch (exception $e){
    var_dump($e);
    }

    // Decode result
    $resultObj = json_decode($result);
    // Send user to final destination, likely callback URL
    if(isset($resultObj->redirect_to)){
        header("Location: ".$resultObj->redirect_to);
    }

}
elseif($_POST['consent_accept'] == "false"){
    error_log("consent.php: Declining Consent");

    $url = $OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/consent/reject?consent_challenge=".$consentChallenge;
    $postData = array('error'=>'request_denied');
    $postJson = json_encode($postData);
    $crl = curl_init($url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_CUSTOMREQUEST,"PUT");
    curl_setopt($crl, CURLOPT_POSTFIELDS, $postJson);
    // Set HTTP Header for POST request 
    curl_setopt($crl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    // Submit the POST request
    try{
        $result = curl_exec($crl);
        //var_dump($result);
    }
    catch (exception $e){
        var_dump($e);
    }

    // Decode result
    $resultObj = json_decode($result);
    if(isset($resultObj->redirect_to)){
        header("Location: ".$resultObj->redirect_to);
        exit();
    }
    exit();
}

?>

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link href="css/custom.css" rel="stylesheet">
    <!-- fontawesome 6 free -->
    <link href="assets/fontawesome6/css/all.css" rel="stylesheet">

     <!-- jQuery -->
     <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <title>Circle.Army - Consent</title>

  </head>
  <body class="bg-dark text-light">

    

    <main class="main form-signin">

        <div id="firstRow" class="row justify-content-center">
            <!-- <i class="fa-solid fa-user fa-10x"></i> -->
            <h1>Consent</h1>
            <img src="<?php echo $client->logo_uri; ?>" width="100px" height="100px">
            <p><?php echo $client->client_name; ?> is requesting access to your information.</p>
            <form method="POST" action="consent.php">
                <input type="hidden" name="consent_challenge" value="<?php echo $consentChallenge; ?>">
                <input type="hidden" name="consent_accept" value="true">

                <?php
                    $i = 0;
                    foreach ($scopes as $scope){
                        $input = <<<EOF
                        <input class="form-check-input" type="checkbox" id="$i-scope" name="$i-scope" value="$scope">
                        <label class="form-check-label" for="$i-scope"> $scope</label><br>
EOF;
                        
                        $i++;
                        echo $input;
                    }

                ?>

<br><br>
                <button class="btn btn-light" type="submit">I Consent</button>

            </form>

            <form method="POST" action="consent.php">
                <input type="hidden" name="consent_challenge" value="<?php echo $consentChallenge; ?>">
                <input type="hidden" name="consent_accept" value="false">
                <button class="btn btn-danger" type="submit">Decline</button>
                </form>
            </form>
        </div>


    </main>

    <?php
    include 'include/footer.php';
    ?>
    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <i id="consent_challenge" hidden><?php echo $consentChallenge;?></i>

    <!--Web3 Stuff-->
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    

  </body>
</html>
