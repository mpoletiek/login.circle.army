<?php
// Dependencies
require './api/include/config.php';
require './api/include/include.php';

// Start Session
session_start();

$hashed_state = hash('sha512',$_GET['state']);
if($hashed_state == $_SESSION['state']){
    error_log("login_check.php: Valid Session");
}
else{
    error_log("login_check.php: Invalid Session");
    exit();
}

// Check login Challenge
if(isset($_GET['challenge_id'])){
    if(preg_match('/'.$_GET['challenge_id'].'/',$_SERVER['HTTP_REFERER'])){
        //error_log('Challenge in Referer');
        $loginChallenge = $_GET['challenge_id'];

        // Get challenge information
        $crl = curl_init($OAUTH2_ADMIN_ENDPOINT.'oauth2/auth/requests/login?login_challenge='.$loginChallenge);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLINFO_HEADER_OUT, true);
        curl_setopt($crl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));

        // Submit the POST request
        try{
        $result = curl_exec($crl);
        }
        catch (exception $e){
            echo "ERROR GETTING LOGIN CHALLENGE";
        var_dump($e);
        }

        $resultObj = json_decode($result);
        //var_dump($resultObj);
        if(!isset($resultObj->client)){
            error_log("login_check.php: Unable to discover login challenge");
            exit();
        }


    }
}
else{
    error_log("login_check.php: Missing Arguments");
    exit();
}

//Acceptable Referers
$authorizedReferers = array('https:login.circle.armylogin.php');
$sub = NULL;

// Check post values
if($_GET['sub']){

    if(strlen($_GET['sub']) >= 20 && strlen($_GET['sub']) <= 44){
        //echo "Address Pass: ".$_POST['user']."\n";

        // verify valid referrers
        $serverReferal = preg_replace('/\//','',$_SERVER['HTTP_REFERER']);
        $matched_ref = 0;
        $quote = '';
        foreach ($authorizedReferers as $auth_ref) {
            $quote = preg_quote($auth_ref);
            if(preg_match('/'.$quote.'/',$serverReferal)){
                $matched_ref = 1;
                $sub = $_GET['sub'];
            }
        }
        if($matched_ref == 0){
            error_log("login_check.php: Wrong Referer: ".$serverReferal);
            exit();
        }
    }
    else{
        error_log("login_check: improper sub");
        exit();
    }
}
else{
    error_log("login_check.php: Missing Arguments");
    exit();
}

// Pull user record if one exists, otherwise create one
$db = pg_connect( "$db_host $db_port $db_name $db_credentials" );
if(!$db){
    error_log("login_check.php: Couldn't connect to db");
	exit();
}

//query DB for user
$get_user_sql = "SELECT * FROM users WHERE sub='".pg_escape_string($sub)."'";
$get_user_ret = pg_query($db,$get_user_sql);
if(!$get_user_ret){
	error_log("login_check.php: Couldn't query db");
	exit();
}
$userRow = pg_fetch_assoc($get_user_ret);

// Check for valid user
if(!isset($userRow['id'])){

    // User does not exist, create user
    header("Location: https://login.circle.army/newuser.php?login_challenge=".$loginChallenge."&sub=".$sub);
    exit();

}

// User captured, do we have a challenge response being offered?
if(isset($_GET['response'])){
    
    // is response available for current user
    if(isset($userRow['auth_response'])){
        
        // does the response match?
        if($userRow['auth_response'] == hash('sha512',$_GET['response'])){

            // Login successful, accept the challenge

            // Accept Login Challenge - Send PUT to accept endpoint
            $data = [ 'subject'=>$userRow['sub'], 'remember'=>true, 'remember_for'=>3600 ];
            $post_data = json_encode($data);
            $crl = curl_init($OAUTH2_ADMIN_ENDPOINT.'oauth2/auth/requests/login/accept?login_challenge='.$loginChallenge);
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($crl, CURLINFO_HEADER_OUT, true);
            curl_setopt($crl, CURLOPT_CUSTOMREQUEST,"PUT");
            curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($crl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            try{
                $result = curl_exec($crl);
                //var_dump($result);
            }
            catch (exception $e){
                var_dump($e);
            }
            curl_close($crl);
            
            // Decode result
            $resultObj = json_decode($result);
            
            // Check result
            if(isset($resultObj->redirect_to)){
                
                header("Location: ".$resultObj->redirect_to);
                exit();
            }
            else{
                error_log("login_check.php: Failed to accept login challenge");
                // Display HTML below
            }
        }
        else{
            // Invalid Password
            error_log("login_check.php: Invalid Password - Login Failed");
            // Display HTML below
        }
    }
    else{
        
        error_log("login_check.php: User has no stored response");
        exit();
    }
}
else{
    error_log("login_check.php: Missing Arguments");
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
    <link href="css/custom.css" rel="stylesheet">

    <title>Circle.Army - Log In</title>

  </head>
  <body class="bg-dark text-light">


    <main class="main bg-dark text-light">
        <div class="px-4 py-5 my-5 text-center">
            <i class="fa-solid fa-users fa-10x"></i>
            <h1 class="display-5 fw-bold">Circle.Army</h1>
            <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">Login Failed</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="window.location.href='<?php echo "https://login.circle.army/login.php?login_challenge=".$_GET['challenge_id']; ?>';">Try Again</button>
            </div>
            </div>
        </div>
    
    
    </main>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="js/navbar.js"></script>

  </body>
</html>
