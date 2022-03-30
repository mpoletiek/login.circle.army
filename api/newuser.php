<?php
// Dependencies
require './include/config.php';
require './include/include.php';

// Setup return
$returnArray = array('message' => 'Failed', 'success' => False, 'result' => array());

//Acceptable Referers
$authorizedReferers = array('https:login.circle.armynewuser.php');
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

if(!isset($_POST['login_challenge']) || !isset($_POST['response']) || !isset($_POST['sub'])){
    $returnArray['message']='Missing Arguments';
    printf(json_encode($returnArray));
    exit();
}
else{
    $loginChallenge = $_POST['login_challenge'];
    $sub = $_POST['sub'];
    $response = $_POST['response'];
}

// Make sure login challenge is still valid
// Get challenge information
$crl = curl_init($OAUTH2_ADMIN_ENDPOINT.'oauth2/auth/requests/login?login_challenge='.$loginChallenge);
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($crl, CURLINFO_HEADER_OUT, true);
// Set HTTP Header for POST request 
curl_setopt($crl, CURLOPT_HTTPHEADER, array(
  'Content-Type: application/json'
));

// Submit the POST request
try{
  $result = curl_exec($crl);
}
catch (exception $e){
  var_dump($e);
}
$resultObj = json_decode($result);
if(!isset($resultObj->client)){
    error_log("api/newuser.php: Failed to fetch login challenge");
    $resultArray['message'] = 'Could not discover login challenge';
    printf(json_encode($returnArray));
    exit();
}

// Make sure user doesn't already exist
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
    error_log("api/newuser.php: User Exists");
    exit();
}

// Login challenge valid and no current user exists, create the new user
$new_user_query = "INSERT INTO users (sub, auth_response) VALUES ('".pg_escape_string($sub)."','".pg_escape_string($response)."')";
$new_user_ret = pg_query($db,$new_user_query);
if(!$new_user_ret){
    //http_response_code(500)
    $returnArray['message']='Could not create user, db query failed.';
    printf(json_encode($returnArray));
    //echo "Failed to create new user\n";
    pg_close($db);
    exit();
}
else{

    $returnArray['message'] = "Login successful";
    $returnArray['result'] = array('login' => true, 'redirect_to' => 'https://login.circle.army/login.php?login_challenge='.$loginChallenge);
    $returnArray['success'] = true;
    $jsonReturn = json_encode($returnArray);
    echo $jsonReturn;
    pg_close($db);
    exit();

}


?>