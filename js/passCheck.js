// Unique function for validating password input

// Disable login button
//$('#login-button').disable();

$('#pass').on('input', function (){
//console.log("this.text: "+this.value);

    checks = 0;
    // is the string 8 characters long?
    if(this.value.length >= 8){
        //console.log("8 Characters");
        $('#passreq1').html('<font color="green">8 character minimum length</font>');
        checks++;
    }
    else{
        // Invalid
        $('#passreq1').html('<font>8 character minimum length</font>');
    }

    // Do we have 1 letter and 1 number?
    if(this.value.match(/[a-z,A-Z]/)){
        //console.log("matched Alpha");
        if(this.value.match(/[0-9]/)){
        //console.log("matched Numeric");
        $('#passreq2').html('<font color="green">Must include 1 letter and 1 number</font>');
        checks++;
        }
        else{
        $('#passreq2').html('<font>Must include 1 letter and 1 number</font>');
        }
    }
    else{
        $('#passreq2').html('<font>Must include 1 letter and 1 number</font>');
    }

    // Do we have a symbol character?
    if(this.value.match(/[^\w\d\s]/)){
        //console.log("matched Symbol");
        $('#passreq3').html("<font color=\"green\">Must include 1 character symbol, ex: '#$%^'</font>");
        checks++;
    }
    else{
        $('#passreq3').html("<font>Must include 1 character symbol, ex: '#$%^'</font>");

    }

    if(checks == 3){
        //console.log("All Password Reqs Satisfied");
        $('#login-button').prop("disabled",false);
    }
    else{
        $('#login-button').prop("disabled",true);
    }

});