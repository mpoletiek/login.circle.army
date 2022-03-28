loginApp = {
    web3Provider: null,
    accounts: [],
    connected: false,
    web3: null,
    chainId: null,
    acceptedNetworks: [ 137 ],
    networkAccepted: false,
    esJsonData: null,
    maticPrice: null,
    latestTXHash: null,
    challenge: null,
    loginChallenge: document.getElementById('challenge_id').innerHTML,
    

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
        console.log("Account[0]: "+loginApp.accounts[0]);
        
        loginApp.web3Provider = window.ethereum;
        console.log("modern dapp browser");
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
        // Get current Blockchain Network and check if we accept
        loginApp.chainId = await loginApp.web3.eth.net.getId();
        console.log("Chain ID: "+loginApp.chainId);

        if(loginApp.acceptedNetworks.includes(loginApp.chainId)){
            console.log("Valid Blockchain Network");
            loginApp.networkAccepted = true;
        }
        
        console.log("loginApp.connected: "+loginApp.connected);

        return loginApp.walletConnected();
        //return App.drawDonateForm();
      }
      else{
        return loginApp.loadingSpinner(false);
        //return App.drawWalletOptions();
          
      }

    },

    walletConnected: function() {
      loginApp.loadingSpinner(false);
      // Query the login db for the user
      // get the sign-in key.
      data = {'sub': loginApp.accounts[0]};

      $.post("/api/login.php", data, function(result){
        console.log(result);
        var jsonResult = jQuery.parseJSON(result);
        console.log("Challenge: "+jsonResult.result['challenge']);
        loginApp.challenge = jsonResult.result['challenge'];

      });
      /*
      if(loginApp.challenge === null){
        console.log("Failed to get challenge");
        $('#login-button').hide();
        $('#status-text'challenge).text('No challenge received');
        return false;
      }*/

      $('#login-button').show();
      $('#status-text').text("Connected");

      return true;
    },

    // Triggered by Login Button
    signSecret: async function() {

      var account = loginApp.accounts[0];
      var params = [loginApp.challenge, account];// The account is the one grabbed at accessing the Wallet (after the User Approval)
      var method = 'personal_sign';

      loginApp.loadingSpinner(true);
      await loginApp.web3Provider.sendAsync({
        method,
        params,
        account,
      }, function (err, result) {
        //result is again an object with fields : result.error, result.result
        //YOU COULD SEND IT TO THE BACKEND FOR VERIFICATION.
        console.log("error: "+result.error);
        console.log("result: "+result.result);
        loginApp.loadingSpinner(false);
        $('#status-text').text("Signed");
        $("#login-button").hide();
        console.log("user: "+loginApp.accounts[0]);
        console.log("pass: "+result.result);
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

      console.log("Login Challenge: "+loginApp.loginChallenge);

      var data = { 'sub' : sub, 'response' : response, 'challenge_id' : loginApp.loginChallenge };
      var resultJson = null;

      $.post('/api/login.php', data, function(result){

          console.log(result);
          resultJson = jQuery.parseJSON(result);
          if(resultJson.result['login'] == true){
            console.log("LOGIN SUCCESSFUL");
            console.log("REDIRECT TO: "+resultJson.result['redirect_to']);
            window.location.href = resultJson.result['redirect_to'];
          }
          else{
            $('#status-text').text("Login Failed");
            $("#login-button").hide();
            return false;
          }

      });

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