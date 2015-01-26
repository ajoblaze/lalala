/* Javascript */
// -------------------------------------------------------------------------------------------------
// Override Event Handler 
// -------------------------------------------------------------------------------------------------

function readyHandler()
{
	// TO DO Defined Here..
	console.log("Ready");
}

function loadHandler()
{
	// TO DO Defined Here..
	console.log("Load");
}

// -------------------------------------------------------------------------------------------------
// Form Event Handler 
// -------------------------------------------------------------------------------------------------

function submitHandler()
{
	// TO DO Defined Here..
	console.log("Submit");
}

function deleteHandler(id)
{

}

// -------------------------------------------------------------------------------------------------
// Custom Event Handler 
// -------------------------------------------------------------------------------------------------

function launchDeleteHandler(extras)
{

}

// Login
$(document).ready(function()
{
	$(".bg-log-loading").hide();
	$("#lnkforget").click(function()
	{
		$("#frmlogin").hide();
		$("#form_forget").fadeIn(500);
	});
	
	$('button#reset_password').click(function()
	{
		username=document.getElementById("forget_username").value;
		if(username=="")
		{
			$("#fterror").html("Please fill your username first.");
		}
		else
		{
			$(".bg-log-loading").show();
			$.ajax({
				url: base_url+"/index/forgetpass",
				type: "POST",
				data: "user="+username,
				success: function(result) {
					$(".bg-log-loading").hide();
					console.log(result);
					if (parseInt(result) == 1)
					{
						$("#forget_username").val("");
						$("#fterror").html("Password has been reset.");
					} else if (parseInt(result) == 2)
					{	
						$("#fterror").html("Username is not registered.");
					} else
					{
						$("#fterror").html("Send mail failed, please check your email");
					}
					
					setTimeout(function()
					{
						$("#fterror").html("");
					}, 3200);
				}
			});
		}
	});
	
	$("button#btback_login").click(function()
	{
		$("#frmlogin").fadeIn(500);
		$("#form_forget").hide();
	});
});