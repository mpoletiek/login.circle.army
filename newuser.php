<?php
// Dependencies
require './api/include/config.php';
require './api/include/include.php';

// Start Session
session_start();

//Acceptable Referers
$authorizedReferers = array('https:login.circle.armylogin.php');
$sub = NULL;

// verify valid referrers
$serverReferal = preg_replace('/\//','',$_SERVER['HTTP_REFERER']);
$matched_ref = 0;
$quote = '';
foreach ($authorizedReferers as $auth_ref) {
    $quote = preg_quote($auth_ref);
    if(preg_match('/'.$quote.'/',$serverReferal)){
        $matched_ref = 1;
    }
}
if($matched_ref == 0){
    $returnArray['message']='quote: '.$quote.'Wrong Referer: '.$serverReferal;
    printf(json_encode($returnArray));
    exit();
}

if(!isset($_GET['login_challenge']) || !isset($_GET['sub'])){
    error_log("newuser.php: Missing Arguments");
    exit();
}
$loginChallenge = $_GET['login_challenge'];
$sub = $_GET['sub'];

// Connect to DB to find out if user actually exists
$db = pg_connect( "$db_host $db_port $db_name $db_credentials" );
if(!$db){
	//http_response_code(400);
    //echo "Error connecting to DB\n";
    $returnArray['message']='Could not connect to db';
    printf(json_encode($returnArray));
	pg_close($db);
	exit();
}
else{
    //echo "Connected to DB\n";
}

//query DB for user
$get_user_sql = "SELECT * FROM users WHERE sub='".pg_escape_string($sub)."'";
$get_user_ret = pg_query($db,$get_user_sql);
if(!$get_user_ret){
	//http_response_code(400);
    $returnArray['message']='could not query sub';
    printf(json_encode($returnArray));
	pg_close($db);
	exit();
}
$userRow = pg_fetch_assoc($get_user_ret);
if(isset($userRow['id'])){
    error_log("newuser.php: User Exists");
    exit();
}

error_log("newuser.php: No user exists, can create new user");






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

    
    <title>Circle.Army - New User</title>

  </head>
  <body class="bg-dark text-light">


    <main class="main form-signin">

        <div id="firstRow" class="row justify-content-center">
            <!-- <i class="fa-solid fa-user fa-10x"></i> -->
            <h1>Create Your Circle.Army Account</h1>
            <p>Enter your desired password.</p>
            <!--<img class="mb-4" src="/docs/5.0/assets/brand/bootstrap-logo.svg" alt="" width="72" height="57">
            <button class="w-100 btn btn-lg btn-primary btn-light" type="submit" onclick="loginApp.init();">Login</button>-->
            
            <div class="input-group mb-3" id="password-input" style="display: none;">
              <span class="input-group-text" id="basic-addon1">Password:</span>
              <input type="password" class="form-control" id="pass" placeholder="Password" aria-label="Password" aria-describedby="basic-addon1">
            </div>

            <div class="input-group mb-3" id="password-input2" style="display: none;">
              <span class="input-group-text" id="basic-addon1">Re-Type Password:</span>
              <input type="password" class="form-control" id="pass2" placeholder="Password" aria-label="Password" aria-describedby="basic-addon1">
            </div>


            <div id="password-text" style="display: none;">
              <p>Password Requirements:</p>
              <ul>
                <li id="passreq1">8-16 characters</li>
                <li id="passreq2">Must include 1 letter and 1 number</li>
                <li id="passreq3">Must include 1 character symbol, ex: '#$%^'.</li>
                <li id="passreq4">Passwords match</li>
              </ul>
            </div>
            <button id="login-button" class="w-100 btn btn-lg btn-primary btn-light" disabled="true" type="submit" style="display:none" onclick="loginApp.signSecret();">Login</button>
            <p id="status-text" class="mt-5 mb-3 text-muted">Checking Web3</p>
        </div>
        <div id="secondRow" class="row justify-content-center">
            <!-- Loading Spinner -->
            <div id="loading-spinner" class="spinner-border text-light" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <!-- FIRST ROW HERE -->
        </div>


    </main>

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <i id="challenge_id" hidden><?php echo $loginChallenge;?></i>

    <!--Web3 Stuff-->
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Login App -->
    <script src="/js/newUser.js"></script>
    <!-- Password Checker -->
    <script src="/js/passCheck.js"></script>

  </body>
</html>
