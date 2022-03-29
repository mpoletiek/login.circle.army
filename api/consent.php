<?php
// Dependencies
require './include/config.php';
require './include/include.php';

// Start Session
session_start();

$json = file_get_contents('php://input');
$post_data = json_decode($json);

error_log("api/consent.php: consent_challenge: ".$post_data->consent_challenge);
error_log("api/consent.php: grant_scope: ".$post_data->grant_scope);
error_log("api/consent.php: sub: ".$post_data->sub);

if(isset($post_data->consent_challenge) && isset($post_data->grant_scope) && isset($post_data->sub)){
    // do stuff
    
    // Generate Access Token
    // Get information about the sub
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
    $get_sub_info = "SELECT * FROM users WHERE sub='".pg_escape_string($post_data->sub)."'";
    $get_sub_ret = pg_query($db,$get_sub_info);
    if(!$get_sub_ret){
        //http_response_code(400);
        $returnArray['message']='could not query sub';
        printf(json_encode($returnArray));
        pg_close($db);
        exit();
    }
    $subRow = pg_fetch_assoc($get_sub_ret);
    error_log("api/consent.php: Found user: ".$subRow['sub']);


    $session = array('access_token'=>array('sub'=>$subRow['sub']), 'id_token'=>array('sub'=>$subRow['sub'], 'provider'=>'https://auth.circle.army/', 'scope'=>$post_data->grant_scope));
    $consentChallenge = $post_data->consent_challenge;
    $grant_scope = explode(' ',$post_data->grant_scope);

    error_log("api/consent.php: Challenge: ".$consentChallenge);
    //error_log("api/consent.php: Scope: ".$grant_scope);


    
    // Accept Consent Challenge
    $url=$OAUTH2_ADMIN_ENDPOINT."oauth2/auth/requests/consent/accept?consent_challenge=".$consentChallenge;
    $postData = array('grant_scope'=>$grant_scope,'session'=>$session,'remember'=>true,'remember_for'=>3600);
    $postJSON = json_encode($postData);
    $crl = curl_init($url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($crl, CURLINFO_HEADER_OUT, true);
    curl_setopt($crl, CURLOPT_CUSTOMREQUEST,"PUT");
    curl_setopt($crl, CURLOPT_POSTFIELDS, $postJSON);
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
    
    // Decode result
    $resultObj = json_decode($result);

    if(isset($resultObj->redirect_to)){
        error_log("api/consent.php: Redirecting to: ".$resultObj->redirect_to);
        $redirect_to = $resultObj->redirect_to;
        $returnJson = array('redirect_to'=>$redirect_to);
        echo json_encode($returnJson);
        curl_close($crl);
        exit();
    }
    echo "<pre>";
    var_dump($result);
    echo "</pre>";

    curl_close($crl);
    
}
else{
    error_log("api/consent.php: Improper arguments");
    exit();
}





?>