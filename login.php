<?php
// Dependencies
require './api/include/config.php';
require './api/include/include.php';

// Start Session
session_start();
$state = generateNonce(64);
$_SESSION['state']=hash('sha512',$state);

// Check arguments
if(isset($_GET['login_challenge'])){
  $loginChallenge = $_GET['login_challenge'];
  //print($loginChallenge);
}
else{
  error_log("login.php: missing login_challenge");
  exit();
}

// Validate Challenge
$crl = curl_init($OAUTH2_ADMIN_ENDPOINT.'oauth2/auth/requests/login?login_challenge='.$loginChallenge);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($crl, CURLINFO_HEADER_OUT, true);
curl_setopt($crl, CURLOPT_HTTPHEADER, array(
  'Content-Type: application/json'
));
try{
  $result = curl_exec($crl);
}
catch (exception $e){
  var_dump($e);
}
// Decode Result
$resultObj = json_decode($result);
// Validate Result
if(isset($resultObj->skip)){
  error_log("login.php: skip set, valid request");
  $skip = $resultObj->skip;
  if($skip){
    //error_log("login.php: already logged-in skipping");
  }
  else{
    //error_log("login.php: skip false, forcing login");
  }
}
else{
  //invalid request
  error_log("login.php: login challenge invalid");
  die();
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

    
    <title>Circle.Army - Login</title>

  </head>
  <body class="bg-dark text-light">


    <main class="main form-signin">

        <div id="firstRow" class="row justify-content-center">
            <i class="fa-solid fa-user fa-10x"></i>
            <!--<img class="mb-4" src="/docs/5.0/assets/brand/bootstrap-logo.svg" alt="" width="72" height="57">
            <button class="w-100 btn btn-lg btn-primary btn-light" type="submit" onclick="loginApp.init();">Login</button>-->
            <p id="status-text" class="mt-5 mb-3 text-muted">Checking Web3</p>
            <div class="input-group mb-3" id="password-input" style="display: none;">
              <span class="input-group-text" id="basic-addon1">Password:</span>
              <input type="text" class="form-control" id="pass" placeholder="Password" aria-label="Password" aria-describedby="basic-addon1">
            </div>
            <div id="password-text" style="display: none;">
              <p>Password Requirements:</p>
              <ul>
                <li id="passreq1">8 character minimum</li>
                <li id="passreq2">Must include 1 letter and 1 number</li>
                <li id="passreq3">Must include 1 character symbol, ex: '#$%^'.</li>
              </ul>
            </div>
            <button id="login-button" class="w-100 btn btn-lg btn-primary btn-light" disabled="true" type="submit" style="display:none" onclick="loginApp.signSecret();">Login</button>
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
    <i id="session_state" hidden><?php echo $state; ?></i>

    <!--Web3 Stuff-->
    <script src="https://cdn.jsdelivr.net/npm/web3@latest/dist/web3.min.js"></script>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Login App -->
    <script src="/js/loginApp.js"></script>
    
    <script src="/js/passCheck.js"></script>
      


    </script>

  </body>
</html>
