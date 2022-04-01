<?php
// Dependencies
require './include/config.php';
require './include/include.php';

// Setup return
$returnArray = array('message' => 'Failed', 'success' => False, 'result' => array());

// Sanitize Request

//Acceptable Referers
$authorizedReferers = array('https:login.circle.armylogin.php');
$sub = NULL;

// Check login Challenge
if(isset($_POST['challenge_id'])){
    if(preg_match('/'.$_POST['challenge_id'].'/',$_SERVER['HTTP_REFERER'])){
        //error_log('Challenge in Referer');
        $loginChallenge = $_POST['challenge_id'];

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
            $returnArray['message']='Unable to discover login challenge';
            printf(json_encode($returnArray));
            exit();
        }


    }
}
else{
    error_log("api/user.php: Missing Arguments");
}


// Check post values
if($_POST['sub']){

    if(strlen($_POST['sub']) >= 20 && strlen($_POST['sub']) <= 44){
        //echo "Address Pass: ".$_POST['user']."\n";

        // verify valid referrers
        $serverReferal = preg_replace('/\//','',$_SERVER['HTTP_REFERER']);
        $matched_ref = 0;
        $quote = '';
        foreach ($authorizedReferers as $auth_ref) {
            $quote = preg_quote($auth_ref);
            if(preg_match('/'.$quote.'/',$serverReferal)){
                $matched_ref = 1;
                $sub = $_POST['sub'];
            }
        }
        if($matched_ref == 0){
            $returnArray['message']='quote: '.$quote.'Wrong Referer: '.$serverReferal;
            printf(json_encode($returnArray));
            exit();
        }
    }
    else{
        $returnArray['message']='sub improperly formatted.';
        printf(json_encode($returnArray));
        exit();
    }
}
else{
    error_log("api/user.php: Missing Arguments");
    exit();
}

// Pull user record if one exists, otherwise create one
$db = pg_connect( "$db_host $db_port $db_name $db_credentials" );
if(!$db){
    error_log("api/user.php: Unable to connect to DB");
	exit();
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

// Check for valid user
if(!isset($userRow['id'])){

    // User does not exist
    $returnArray['message'] = "User does not exist.";
    $returnArray['result'] = array('user' => false);
    $returnArray['success'] = true;
    $jsonReturn = json_encode($returnArray);
    echo $jsonReturn;
    pg_close($db);
    exit();

}
else{
    // User exists
    $returnArray['message'] = "User exists.";
    $returnArray['result'] = array('user' => true);
    $returnArray['success'] = true;
    $jsonReturn = json_encode($returnArray);
    echo $jsonReturn;
    pg_close($db);
    exit();
}




?>