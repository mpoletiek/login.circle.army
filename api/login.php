<?php
// Dependencies
require './include/config.php';
require './include/include.php';

// Start Session
session_start();


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

// Check post values
if($_POST && $_POST['sub']){

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
    $returnArray['message']='sub not provided';
    printf(json_encode($returnArray));
    exit();
}

// Pull user record if one exists, otherwise create one
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

// Check for valid user
if(!isset($userRow['id'])){

    // User does not exist, create user

    // Redirect to user creation
    $returnArray['message'] = "Login successful";
    $returnArray['result'] = array('login' => true, 'redirect_to' => 'https://login.circle.army/newuser.php?login_challenge='.$loginChallenge.'&sub='.$sub);
    $returnArray['success'] = true;
    $jsonReturn = json_encode($returnArray);
    echo $jsonReturn;
    pg_close($db);
    exit();

}

// User captured, do we have a challenge response being offered?
if(isset($_POST['response'])){
    
    // is response available for current user
    if(isset($userRow['auth_response'])){
        
        // does the response match?
        if($userRow['auth_response'] == $_POST['response']){

            // Login successful, accept the challenge
            // Accept Login Challenge
            $data = [ 'subject'=>$userRow['sub'], 'remember'=>true, 'remember_for'=>3600 ];
            $post_data = json_encode($data);
            // Get challenge information
            $crl = curl_init($OAUTH2_ADMIN_ENDPOINT.'oauth2/auth/requests/login/accept?login_challenge='.$loginChallenge);
            curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($crl, CURLINFO_HEADER_OUT, true);
            curl_setopt($crl, CURLOPT_CUSTOMREQUEST,"PUT");
            //var_dump($post_data);
            curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
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
            curl_close($crl);

            // Decode result
            $resultObj = json_decode($result);
            // Check result
            if(isset($resultObj->redirect_to)){
                // Result has a redirect_to
                $returnArray['message'] = "Login successful";
                $returnArray['result'] = array('login' => true, 'redirect_to' => $resultObj->redirect_to);
                $returnArray['success'] = true;
                $jsonReturn = json_encode($returnArray);
                echo $jsonReturn;
                pg_close($db);
                exit();
            }
            else{
                // no redirect_to provided
                $returnArray['message'] = 'Failed to authenticate login challenge';
                printf(json_encode($returnArray));
                pg_close($db);
                exit();
            }
        }
        else{
            // Invalid Password
            $returnArray['message']='Login Failed';
            $returnArray['result']=array('login'=>false);
            printf(json_encode($returnArray));
            exit();
        }
    }
    else{
        
        $returnArray['message']='No response provided';
        printf(json_encode($returnArray));
        pg_close($db);
        exit();
    }
}
else{
    // No pass offered, return challenge
    $returnArray['result'] = array("challenge" => $userRow['challenge']);
    $returnArray['message'] = "Challenge presented";
    $returnArray['success'] = true;
    printf(json_encode($returnArray));
    pg_close($db);
    exit();
}




?>