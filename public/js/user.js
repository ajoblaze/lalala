/* Javascript Document */
// Variable
var hilang =false;
var pernah=false;
var notif = $("#hid_user_notif").val();

// -------------------------------------------------------------------------------------------------
// AngularJs
// -------------------------------------------------------------------------------------------------
var by = new Array();
var sort = new Array();
var result = $("#hid_user_data").html();
var project = $("#project").val();

var userApp = angular.module("UserApp",[]);

userApp.controller("userCtrl",function($scope){
	$scope.query = {}
	$scope.queryBy = '$'
	$scope.users = $.parseJSON(result);
	console.log(result);
	$scope.orderProp="userID"; 

	$scope.projectFilter = function(data)
	{
		return (data.projectName.indexOf(project) >= 0) ? true : false;
	};
	
	$scope.remove = function($event, data)
	{
		var conf = new Object();
		conf.extras = new Object();
		conf.text = "Are you sure want to remove this user ?";
		conf.OKHandler = launchDeleteHandler;
		conf.extras.id = data.userID;
		confirmBefore(conf);
	};
});

userApp.filter("asDate", function () {
    return function (input) {
		return new Date(input.replace(/-/g, "/").replace(".",""));
    }
});	

// -------------------------------------------------------------------------------------------------
// Override Event Handler 
// -------------------------------------------------------------------------------------------------
function readyHandler()
{
	// TO DO Defined Here..
	console.log("Ready");
	
	if (notif != "" && typeof notif != "undefined") {
		showAlert(ALERT_TYPE_SUCCESS, notif);
	}
	
	// Checking role
	switch($("#selectRole").val())
	{
		case sa:
			$("#selectProjects").prop("disabled",true);
			$(".proj[value='201408PR0001']").attr("selected", true);
			$(".proj[value='201408PR0002']").attr("selected", true);
			$(".proj[value='201409PR0003']").attr("selected", true);
			$(".proj[value='201409PR0004']").attr("selected", true);
			$(".proj[value='201409PR0005']").attr("selected", true);
			$(".chkProject").attr("checked", true);
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_segment_list").hide();
			showTips(sa);
			break;
		case ad:
			$("#selectProjects").prop("disabled",false);
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").hide();
			showTips(ad);
			break;
		case cs:
			$("#selectProjects").prop("disabled",false);
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").hide();
			showTips(cs);
			break;
		case ma:
			$("#selectProjects").prop("disabled",true);
			$("#sgift").prop("checked", true);
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_provider").show();
			$("#panel_merchant_list").show();
			$("#panel_segment_list").hide();
			showTips(ma);
			break;
		case mr :
			$("#selectProjects").prop("disabled",true);
			$("#sgift").prop("checked", true);
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide()
			$("#panel_merchant_type").show();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").hide();
			showTips(mr);
			break;
		case po:
			$("#selectProjects").prop("disabled",false);
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").hide();
			showTips(po);
			break;
		case pm:
			$("#selectProjects").prop("disabled",false);
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").show();
			showTips(pm);
			break;
		case sr:
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").hide();
			showTips(sr);
			break;
		case pu:
			$("#selectProjects").prop("disabled",true);
			$(".proj").attr("selected", false);
			$(".chosen-select").trigger("chosen:updated");
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").show();
			$("#panel_segment_list").hide();
			showTips(pu);
			break;
		case cspm:
			$(".proj").attr("selected", false);
			$(".chosen-select").trigger("chosen:updated");
			$("#panel_provider").hide();
			$("#panel_merchant_list").hide();
			$("#panel_merchant_type").hide();
			$("#panel_publisher_type").hide();
			$("#panel_segment_list").hide();
			showTips(cspm);
			break;
	}
	$(".chosen-select").trigger("chosen:updated");
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
	console.log("Delete");
	// Added by a.riccia (Albert Ricia)
	// 18 / 08 / 2014
	// Confirming Before deleting
	
}

// -------------------------------------------------------------------------------------------------
// Custom Event Handler 
// -------------------------------------------------------------------------------------------------

function launchDeleteHandler(extras)
{
	window.location = base_url + "/user/delete?id=" + extras.id;
}

function showTips(role)
{
	if (role == sa) { $("#sa").show(); } else { $("#sa").hide(); }
	if (role == ad) { $("#ad").show(); } else { $("#ad").hide(); }
	if (role == cs) { $("#cs").show(); } else { $("#cs").hide(); }
	if (role == mr) { $("#me").show(); } else { $("#me").hide(); }
	if (role == ma)	{ $("#ma").show(); } else { $("#ma").hide(); }
	if (role == po) { $("#po").show(); } else { $("#po").hide(); }
	if (role == pm) { $("#pm").show(); } else { $("#pm").hide(); }
	if (role == sr) { $("#sr").show(); } else { $("#sr").hide(); }
	if (role == pu) { $("#pu").show(); } else { $("#pu").hide(); }
	if (role == cspm) { $("#cspm").show(); } else { $("#cspm").hide(); }
}

// Event --
$(document).ready(function(e)
{
	$("#advanced_search_container").hide();
	$("#advanced_search_button").click(function(){
		if(pernah && !hilang){
			$("#advanced_search_container").slideUp();
			//$("#input_search").removeAttr("disabled");
			$("#search_button").removeAttr("disabled");
			$(".adv-search").removeClass("up");
			$(".adv-search").addClass("down");
			hilang=true;
		}
		else{
			$("#advanced_search_container").slideDown();
			//$("#input_search").attr("disabled","disabled");
			$("#search_button").attr("disabled","disabled");
			$(".adv-search").removeClass("down");
			$(".adv-search").addClass("up");
			hilang=false;
		}
		pernah=true;
	});
	
	$(".btn-delete").click(function(e)
	{	
		var id = $(this).attr("data-id");
		deleteHandler(id);
	});
	
	// ----------------------------------------------------------
	// Insert User
	// ----------------------------------------------------------
	$("#aggregator").change(function(e)
	{
		$(".chkProject").prop("disabled",true);
		$(".chkProject").attr("checked", false);
		$("#sgift").prop("checked", true);
		$("#panel_merchant_type").hide();
		$("#panel_provider").show();
		$("#panel_merchant_list").show();
	});
	
	$("#201408RL0000").change(function(e)
	{
		//$(".chkProject").attr("checked", true);
		$(".chkProject").prop("disabled",true);
		$("#panel_merchant_type").hide();
		$("#panel_provider").hide();
		$("#panel_merchant_list").hide();
	});
	
	$("#merchant").change(function(e)
	{
		$(".chkProject").prop("disabled",true);
		$(".chkProject").attr("checked", false);
		$("#sgift").prop("checked", true);
		$("#panel_provider").hide();
		$("#panel_merchant_list").hide()
		$("#panel_merchant_type").show();
	});
	
	$("#selectRole").change(function(e)
	{
		switch($(this).val())
		{
			case sa:
				$("#selectProjects").prop("disabled",true);
				$(".proj").attr("selected", false);
				$(".proj[value='201408PR0001']").attr("selected", true);
				$(".proj[value='201408PR0002']").attr("selected", true);
				$(".proj[value='201409PR0003']").attr("selected", true);
				$(".proj[value='201409PR0004']").attr("selected", true);
				$(".proj[value='201409PR0005']").attr("selected", true);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_segment_list").hide();
				showTips(sa);
				break;
			case ad:
				$("#selectProjects").prop("disabled",false);
				$(".proj").attr("selected", false);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").hide();
				showTips(ad);
				break;
			case cs:
				$("#selectProjects").prop("disabled",false);
				$(".proj").attr("selected", false);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").hide();
				showTips(cs);
				break;
			case ma:
				$("#selectProjects").prop("disabled",true);
				$(".proj").attr("selected", false);
				$(".proj[value='201408PR0002']").attr("selected", true);
				$(".chosen-select").trigger("chosen:updated");
				$("#sgift").prop("checked", true);
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_provider").show();
				$("#panel_merchant_list").show();
				$("#panel_segment_list").hide();
				showTips(ma);
				break;
			case mr :
				$("#selectProjects").prop("disabled",true);
				$(".proj").attr("selected", false);
				$(".proj[value='201408PR0002']").attr("selected", true);
				$(".chosen-select").trigger("chosen:updated");
				$("#sgift").prop("checked", true);
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide()
				$("#panel_merchant_type").show();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").hide();
				showTips(mr);
				break;
			case po:
				$("#selectProjects").prop("disabled",false);
				$(".proj").attr("selected", false);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").hide();
				showTips(po);
				break;
			case pm:
				$("#selectProjects").prop("disabled",false);
				$(".proj").attr("selected", false);
				$(".proj[value='201408PR0001']").attr("selected", true);
				$(".proj[value='201408PR0002']").attr("selected", true);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").show();
				showTips(pm);
				break;
			case sr:
				$(".proj").attr("selected", false);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").hide();
				showTips(sr);
				break;
			case pu:
				$("#selectProjects").prop("disabled",true);
				$(".proj").attr("selected", false);
				$(".proj[value='201408PR0001']").attr("selected", true);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").show();
				$("#panel_segment_list").hide();
				showTips(pu);
				break;
			case cspm:
				$("#selectProjects").prop("disabled",false);
				$(".proj").attr("selected", false);
				setTimeout(function(){ $(".proj[value='201408PR0001']").attr("selected", true); } , 500);
				$(".chosen-select").trigger("chosen:updated");
				$("#panel_provider").hide();
				$("#panel_merchant_list").hide();
				$("#panel_merchant_type").hide();
				$("#panel_publisher_type").hide();
				$("#panel_segment_list").hide();
				showTips(cspm);
				break;
		}
	});
	
	$(".role:not(#aggregator, #merchant, #201408RL0000)").change(function(e)
	{
		$(".chkProject").prop("disabled", false);
		$(".chkProject").attr("checked", false);
		$("#panel_merchant_type").hide();
		$("#panel_provider").hide();
		$("#panel_merchant_list").hide();
	});
});