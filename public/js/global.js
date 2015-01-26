/* Javascript Document */
// Global Variable
var flag = 1;
var lock = false;

var ALERT_TYPE_SUCCESS = "success";
var ALERT_TYPE_FAILED = "failed";
var ALERT_TYPE_WARNING = "warning";

var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;
var STR_PAD_BOTH = 3;

var PRM_SUSPEND = "disabled";
var PRM_ACTIVATE = "enabled";

var TIME_MONTH = "monthly";
var TIME_WEEK = "weekly";
var TIME_DAY = "daily";

// List of Role
var sa = "201408RL0000";
var ad = "201408RL0001";
var cs = "201408RL0002";
var ma = "201408RL0003";
var mr = "201408RL0004";
var po = "201408RL0005";
var pm = "201408RL0006";
var sr = "201408RL0007";
var pu = "201408RL0008";
var cspm = "201408RL0009";

// Handle Event
$(document).ready(function(e)
{
	checkRole();

	if (typeof readyHandler != "undefined")
		readyHandler();
	
	$('.accordion').click(function() {
		var idx = $(this).data("id");
		var flg=$('#accordion-down'+idx).css("display");
		if(flg=="none")
		{
			$("#crt"+idx).addClass("active");
			$('#accordion-down'+idx).slideDown(500);
			$('#accordion-down'+idx).css("margin-left","20px");
			$('.accordion[data-id="'+idx+'"]').css("background-color", "#F5F5F5");
		}else{
			$("#crt"+idx).removeClass("active");
			$('#accordion-down'+idx).slideUp(500);
			$('.accordion[data-id="'+idx+'"]').css("background-color", "#FFFFFF");
		}
	});
	
	// Set Common Event
	$("#custom_form").submit(function(e)
	{
		if (typeof submitHandler != "undefined")
			submitHandler();
	});
	
	$("#btsendmail").click(function(e)
	{
		var to = $("#cbto").val();
		var email = $("#hid_email").val();
		var deviceid = $("#hid_email_deviceid").val();
		var devicetype = $("#hid_email_devicetype").val();
		var name = $("#hid_email_name").val();
		var app = $("#hid_email_app").val();
		var mode = $("#hid_email_status").val();
		var reason = $("#reason_email").val();
		
		console.log("Email : " + email + ", name : " + name + ",app : " + app + ", to : " + to + ", device_identifier : " + deviceid + ", device_type : " + devicetype);
		if (to == null) {
			$("#errorMailText").html("Please fill destination email.");
			$("#errorMail").fadeIn("fast");
			
			setTimeout(function() { $("#errorMail").fadeOut("fast");}, 3200);
		} else {
			$("#progressbar").show();
			$.ajax({
				url : base_url+"/customer/sendconfirm",
				type : "POST",
				data : "email="+email+"&device_identifier="+deviceid+"&device_type="+devicetype+"&name="+name+"&app="+app+"&reason="+reason+"&to="+to+"&mode="+mode,
				dataType : "html",
				success : function(result)
				{
					$("#progressbar").hide();
					$(".close").click();
					console.log(result);
					if (parseInt(result) == 1) {
						console.log("Suspend has been sent..");
						showAlert(ALERT_TYPE_SUCCESS, "Suspend has been sent..");
					} else if (parseInt(result) == -1) {
						alert("Please fill destination email.");
						showAlert(ALERT_TYPE_WARNING, "Please fill destination email.");
					} else {
						console.log("Send mail failed.");
						showAlert(ALERT_TYPE_FAILED, "Send mail failed.");						
					}
				
				}
			});
		}
	});
	
	$("#btchange_email").click(function(e)
	{
		var deviceid = $("#hid_email_deviceid").val();
		var devicetype = $("#hid_email_devicetype").val();
		var name = $("#hid_email_name").val();
		var email = $("#hid_email").val();
		var app = $("#hid_email_app").val();
		var type = $("#hid_email_devicetype").val();
		var newemail = $("#newemail").val();
		var mode = $("#hid_email_status").val();
		var regex = new RegExp("^.+@.+\..+$");
		var to = $("#cbtoChange").val();
		var reason = $("#reasonChange").val();
		
		if (newemail == "") {
			$("#errorChangeText").html("Please fill new email.");
			$("#errorChange").fadeIn("fast");
			
			setTimeout(function() { $("#errorChange").fadeOut("fast");}, 3200);
		} else if (!regex.test(newemail)) {
			$("#errorChangeText").html("Invalid email format.");
			$("#errorChange").fadeIn("fast");
			
			setTimeout(function() { $("#errorChange").fadeOut("fast");}, 3200);
		} else {
			$("#progresschangebar").show();
			$.ajax({
				url : base_url + "/customer/sendconfirm",
				type : "POST",
				dataType : "html",
				data : "name="+name+"&email="+email+"&to="+to+"&newemail="+newemail+"&app="+app+"&type="+type+"&device_identifier="+deviceid+"&device_type="+devicetype+"&mode="+mode+"&reason="+reason,
				success : function(result)
				{
					$("#progresschangebar").hide();
					$(".close").click();
					console.log(result);
					if (parseInt(result) == 1) {
						console.log("Suspend has been sent..");
						showAlert(ALERT_TYPE_SUCCESS, "Request has been sent..");
					} else if (parseInt(result) == -1) {
						alert("Please fill destination email.");
						showAlert(ALERT_TYPE_WARNING, "Please fill destination email.");
					} else {
						console.log("Send mail failed.");
						showAlert(ALERT_TYPE_FAILED, "Send mail failed.");						
					}
				},
				error : function (result)
				{
					console.log("AJAX Error");
				}
			});
		}
	});
	
	$(".btn-delete").click(function(e)
	{
		var id = $(this).attr("data-id");
		if (typeof deleteHandler != "undefined")
			deleteHandler(id);
	});
	
	$(".chosen-select").chosen({
		width: "100%"
	});
	
	$(".date").datepicker({
		changeMonth : true,
		changeYear : true,
		dateFormat : "yy-mm-dd"
	});
	
	$(".num").keydown(function(e)
	{
		if ((e.keyCode < 48 || e.keyCode > 57) && e.keyCode != 8 && e.keyCode != 46 && (e.keyCode < 96 || e.keyCode > 105) && (e.keyCode < 37 || e.keyCode > 40))  {
			return false;
		} 
	});
	
	$(function () {
	  $('[data-toggle="tooltip"]').tooltip()
	});
	
	$("#wrapper").css({"transition":"none"});
	$("#wrapper").addClass("active");
	$("#menu-toggle").addClass("btn_clicked");
	$('.menu-link').bigSlide();
	
	// stop propagation of shown event                                     
	$('[data-toggle="tab"]').on('shown', function(e){ e.stopPropagation() });
});

$(window).load(function(e)
{
	resizing();
	moveScroll();
	if (typeof loadHandler != "undefined")
		loadHandler();
});

$(window).resize(function(e)
{	
	resizing();
	console.log($("#transaction_table").height() + "," + $(window).height());
});
function moveScroll(){
		$('#menu').find('a').each(function(i){
		if($(this).css('background-color')=="rgb(51, 51, 51)"){
			$('#menu').scrollTop(parseInt($(this).offset().top));
		};
	});
}
function resizing()
{	
	$("#transaction_table tbody").css("height", (0.605 * $(window).height()) + "px");
	$("#doug-neiner-five tbody").css("height", (0.605 * $(window).height()) + "px");
	$("#log_table tbody").css("height", (0.605 * $(window).height()) + "px");
	$("#container-unin").css("width",($("body").width()*0.935)-35);
	$("#container-unin-apps").css("width",($("body").width()*0.935)-35);
}

function checkRole()
{
	// Customer Service
	var role = $("#hid_role").val();
	switch(role) {
		case sa : $(".sa-not-show").hide(); break;
		case ad : $(".ad-not-show").hide(); break; 
		case cs : $(".cs-not-show").hide(); break;
		case ma : $(".ma-not-show").hide(); break;
		case mr : $(".mr-not-show").hide(); break;
		case po : $(".po-not-show").hide(); break;
		case pm : $(".pm-not-show").hide(); break;
		case sr : $(".sr-not-show").hide(); break;
		case pu : $(".pu-not-show").hide(); break;
		case cspm : $(".cspm-not-show").hide(); break;
	}
	
	if (role != sa && role != ad) {
		$(".beside-ad-not-show").hide();
	}
}

/* Helper Function */
function showAlert(type, text)
{
 	$("#alertDialog").removeClass("alert-danger");
	$("#alertDialog").removeClass("alert-success");
	$("#alertDialog").removeClass("alert-warning");
	
	switch(type) {
		case ALERT_TYPE_SUCCESS: $("#alertDialog").addClass("alert-success");
								 $("#textHeader").html("SUCCESS!");
								 break;
		case ALERT_TYPE_FAILED: $("#alertDialog").addClass("alert-danger");
								$("#textHeader").html("ERROR!");
								break;
		case ALERT_TYPE_WARNING: $("#alertDialog").addClass("alert-warning");
								$("#textHeader").html("WARNING!");
								 break;
	}
	
	$("#alertDialogText").html(text);
	$("#alertDialog").fadeIn("fast");
				
	setTimeout(function() { $("#alertDialog").fadeOut("fast");}, 3600);
}


function confirmBefore(config)
{
	var conf = confirm(config.text);
	if (conf == true)
	{
		if (typeof config.OKHandler != "undefined")
			config.OKHandler(config.extras);
	}
}

function formatNumber(number, decimal_places)
{
	if (isNaN(number)) return '-';
	decimal_places = (typeof decimal_places != "undefined") ? decimal_places : 0;
    var number = new Number(number).toFixed(decimal_places) + '';
    var x = number.split('.');
    var x1 = x[0];
    var x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

function pad(str, len, pad, dir) {

    if (typeof(len) == "undefined") { var len = 0; }
    if (typeof(pad) == "undefined") { var pad = ' '; }
    if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }

    if (len + 1 >= str.length) {

        switch (dir){

            case STR_PAD_LEFT:
                str = Array(len + 1 - str.length).join(pad) + str;
            break;

            case STR_PAD_BOTH:
                var right = Math.ceil((padlen = len - str.length) / 2);
                var left = padlen - right;
                str = Array(left+1).join(pad) + str + Array(right+1).join(pad);
            break;

            default:
                str = str + Array(len + 1 - str.length).join(pad);
            break;

        } // switch

    }
    return str;
}

function JS_decode(str) {
	var div = document.createElement('div'); 
	div.innerHTML = str ;
	var decoded = div.firstChild.nodeValue; 
	return decoded ;
}