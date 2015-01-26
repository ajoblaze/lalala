// Javascript Document
// Global Variable
var first_time = $("#hid_user_flag").val();

if(first_time==0){
	$('#container-changepass').show();
	$('#bg-overlay').show();
}
else{
	$('#container-changepass').hide();
	$('#bg-overlay').hide();
}

$(document).ready(function(e)
{
	$("#newpass,#confirm").keypress(function(e)
	{
		if (e.keyCode == 13) {
			$("#btchange").click();
		}
	});
	
	$("#btchange").click(function(e)
	{
		var newpass = $("#newpass").val();
		var confirm = $("#confirm").val();
		
		if (newpass == "") {
			$("#lblerror").html("New Password must be filled.");
		} else if (newpass.length < 6) {
			$("#lblerror").html("Password at least contains 6 characters.");
		} else if (confirm != newpass) {
			$("#lblerror").html("Confirm password must be same with the New Password.");
		} else {
			$.ajax({
				url : base_url+"/main/firstchange",
				type : "POST",
				dataType : "html",
				data : "newpass="+newpass+"&confirm="+confirm,
				success : function(result)
				{
					console.log(result);
					switch(parseInt(result))
					{
						case 1: $("#lblerror").html("New Password must be filled.");break;
						case 2: $("#lblerror").html("Password at least contains 6 characters.");break;
						case 3: $("#lblerror").html("Password must be alphanumeric.");break;
						case 4: $("#lblerror").html("Confirm password must be same with the New Password.");break;
						case 5: $("#lblerror").html("New password can't be same with old");break;
						case 0: $("#container-changepass").fadeOut("fast");
								$("#bg-overlay").fadeOut("fast");
								showAlert(ALERT_TYPE_SUCCESS, "Change password successfully.");break;
					}
				}
			});
		}
		window.setTimeout(function(){ $("#lblerror").html(""); },3800);
	});
});