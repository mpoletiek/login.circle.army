<?php

// Dependencies
require './api/include/config.php';
require './api/include/include.php';

// Start Session
session_start();


// Get logout challenge
if(isset($_GET['logout_challenge'])){
  $logoutChallenge = $_GET['logout_challenge'];
}
elseif(isset($_POST['logout_challenge'])){
    $logoutChallenge = $_POST['logout_challenge'];
}
else{
  error_log("login.php: missing logout_challenge");
  exit();
}
error_log("logout.php: Received logout challenge ".$logoutChallenge);

// Get logout challenge information
$url=$OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/logout?logout_challenge=".urlencode($logoutChallenge);
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

if(!isset($resultObj->subject)){
    error_log("logout.php: Invalid Challenge");
    exit();
}

// Are we consenting?
if($_POST['logout_accept'] == "true"){
    error_log("logout.php: Accepting");
    
    $url = $OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/logout/accept?logout_challenge=".$logoutChallenge;

    $postData = array('logout_challenge'=>$logoutChallenge);
    $postJson = json_encode($postData);

    $crl = curl_init($url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($crl, CURLOPT_POSTFIELDS,$postJson);
    // Set HTTP Header for POST request 
    curl_setopt($crl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json'
    ));

    // Submit the POST request
    try{
        $result = curl_exec($crl);
        var_dump($result);
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
    exit();

}
elseif($_POST['logout_accept'] == "false"){
    error_log("logout.php: Declining Logout");

    $url = $OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/logout/reject?logout_challenge=".$logoutChallenge;
    
    $crl = curl_init($url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_CUSTOMREQUEST,"PUT");
    // Set HTTP Header for POST request 
    curl_setopt($crl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    // Submit the POST request
    try{
        $result = curl_exec($crl);
        var_dump($result);
    }
    catch (exception $e){
        var_dump($e);
    }

    // Redirect to provider home
    header("Location: https://www.circle.army/home.php");
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
            <i class="fa-solid fa-user fa-10x"></i>
            <p>Are you sure you wish to log out from <?php echo $client->client_name; ?>?</p>
            <form method="POST" action="logout.php">
                <input type="hidden" name="logout_challenge" value="<?php echo $logoutChallenge; ?>">
                <input type="hidden" name="logout_accept" value="true">


<br><br>
                <button class="btn btn-light" type="submit">Log Out</button>

            </form>

            <form method="POST" action="logout.php">
                <input type="hidden" name="logout_challenge" value="<?php echo $logoutChallenge; ?>">
                <input type="hidden" name="logout_accept" value="false">
                <button class="btn btn-danger" type="submit">Cancel</button>
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
