var hilang =false;
var pernah = false;
var notif = $("#hid_user_notif").val();
function readyHandler()
{
	// TO DO Defined Here..
	console.log("Ready");
	isNumber();
	
	if (notif != "" && typeof notif != "undefined") {
		console.log(notif)
		if(notif=='failed'){
			showAlert(ALERT_TYPE_FAILED, 'Fail to insert data');
		}
		else if(notif=='success'){
			showAlert(ALERT_TYPE_SUCCESS, 'Success insert data');
		} else if (notif != "nothing"){
			showAlert(ALERT_TYPE_FAILED, notif);
		}
	}
}
function isNumber(){
	var str = $('#daily_install').val();
	var hasError = false;
	for(var i=0;i<str.length;i++){
		if('0'>str[i] || str[i]>'9'){
			hasError =true;
			break;
		}
	}
	
	if(hasError){
		$('#div_daily_install').addClass("has-error");
		$('#div_error_daily_install').show();
	}
	else{
		$('#div_daily_install').removeClass("has-error");
		$('#div_error_daily_install').hide();
	}
}
// JQUERY
$(document).ready(function(){
	$( "#date_text" ).datepicker({maxDate : "-0d",dateFormat: 'dd M yy' });
	$('#daily_install').keyup(function(e){
		isNumber();
	});
});

