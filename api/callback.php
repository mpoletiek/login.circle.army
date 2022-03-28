<?php

session_start();

if($_SERVER['HTTP_REFERER'] != 'https://www.circle.army/api/oauth_test.php'){
    exit();
}
if(!isset($_SESSION['hashed_secret']) || !isset($_SESSION['nonce']) || !isset($_GET['state']) || !isset($_GET['nonce'])){
    exit();
}

echo $_SESSION['hashed_secret'];
echo "<br>";
echo $_GET['state'];
echo "<br>";
echo $_GET['nonce'];

if($_GET['nonce'] == $_SESSION['nonce']){
    echo "<br>";
    echo "Nonce Matched<br>";
    //$challenge = hash('sha256',)
    // get token
    $pattern="/token=(.*)/";
    preg_match($pattern,$_GET['state'],$matches);
    $challenge = hash('sha512',$matches[1]);
    echo "Challenge: ".$challenge."<br>";
    if($challenge == $_SESSION['hashed_secret']){
        echo "<br>Session Secured<br>";



    }
    else{
        echo "<br>Session Mismatch<br>";
    }
}
else{
    echo "<br>";
    echo "Nonce Mismatch";
}


unset($_SESSION);
session_destroy();

?>