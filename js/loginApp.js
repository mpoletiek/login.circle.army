loginApp = {
    web3Provider: null,
    accounts: [],
    connected: false,
    web3: null,
    chainId: null,
    networkAccepted: false,
    challenge: null,
    loginChallenge: document.getElementById('challenge_id').innerHTML,
    state: document.getElementById('session_state').innerHTML,
    

    // This is the first function called. Here we can setup stuff needed later
    init: async function() {
        // Show loading spinner
        loginApp.loadingSpinner(true);

        return await loginApp.initWeb3();
    },
  
    // Initialize Web3
    initWeb3: async function() {
  
        // First we check to see which type of Web3 we're using.
      // Modern dapp browsers...
      if (window.ethereum){
        try {
          //Request account access
          loginApp.accounts = await window.ethereum.request({ method: "eth_requestAccounts" });
          loginApp.connected = true;
        } catch (error) {
          // User denied account access...
          console.error("User denied account access");
          loginApp.connected = false;
          // Set proper message on UI
          $('#status-text').text("Wallet Declined");
          return loginApp.loadingSpinner(false);
        }
        
        // User granted access to accounts
        //console.log("Account[0]: "+loginApp.accounts[0]);
        
        loginApp.web3Provider = window.ethereum;
        console.log("modern dapp browser");

        var data = { 'sub' : loginApp.accounts[0], 'challenge_id' : loginApp.loginChallenge };
        var resultJson = null;
        // Let's find out if a user already exists
        $.post('/api/user.php', data, function(result){

          //console.log("DOES USER EXIST: "+result);
          resultJson = jQuery.parseJSON(result);
          if(resultJson.result['user'] == false){
            // User Does Not Exist, redirect to new user page
            newUrl = 'https://login.circle.army/newuser.php?login_challenge='+loginApp.loginChallenge+'&sub='+loginApp.accounts[0];
            window.location.href=newUrl;
          }

        });

      }
      // Legacy dapp browsers...
      else if (window.web3) {
          try {
            loginApp.web3Provider = window.web3.currentProvider;
            loginApp.accounts = window.eth.accounts;
            console.log("legacy dapp browser");
          } catch (error) {
              console.error("User denied account access");
              loginApp.connected = false;
              $('#status-text').text("Wallet Declined");
              return loginApp.loadingSpinner(false);
          }
        
      }
      else{
          // Failed to connect to wallet or wallet account access denied
          loginApp.connected = false;
      }

      // Initialize Web3
      if(loginApp.connected){
        
        loginApp.web3 = new Web3(loginApp.web3Provider);
        // Get current Blockchain Network
        loginApp.chainId = await loginApp.web3.eth.net.getId();
        
        return loginApp.walletConnected();
      }
      else{
        return loginApp.loadingSpinner(false);    
      }

    },

    walletConnected: function() {
      loginApp.loadingSpinner(false);
      
      // We're connected, show password prompt and check for valid passwords
      $('#login-button').show();
      $('#password-input').show();
      $('#password-text').show();
      $('#status-text').text("Wallet Connected");

      return true;
    },

    // Triggered by Login Button
    signSecret: async function() {

      var msg = await $('#pass').val();
      //console.log("msg: "+msg);
      var account = loginApp.accounts[0];
      var params = [msg, account];// The account is the one grabbed at accessing the Wallet (after the User Approval)
      var method = 'personal_sign';

      loginApp.loadingSpinner(true);
      await loginApp.web3Provider.sendAsync({
        method,
        params,
        account,
      }, function (err, result) {
        //result is again an object with fields : result.error, result.result
        loginApp.loadingSpinner(false);
        $('#status-text').text("Signed");
        $("#login-button").hide();

        if(err && err.code == 4001){
          console.log(err);
          $('#status-text').text("Login Failed");
          $("#login-button").hide();
        }
        else {
          // console.log("Unknown error");
          return loginApp.loginAttempt(loginApp.accounts[0],result.result);
        }
      });

    },

    loginAttempt: function(sub,response){

      //console.log("Login Challenge: "+loginApp.loginChallenge);
      redirect_to = 'https://login.circle.army/login_check.php?sub='+sub+'&response='+response+'&challenge_id='+loginApp.loginChallenge+'&state='+loginApp.state;
      window.location.href=redirect_to;
      
    },

    loadingSpinner: function(show) {
        if(show == true){
            $('#loading-spinner').show();
        }
        else{
            $('#loading-spinner').hide();
        }
    }
}




// Execute the app when the DOM is ready
$(function() {
    $(document).ready(function() {
        loginApp.init();
    });
});