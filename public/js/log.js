// Javascript
var hilang =false;
var pernah = false;
var by = new Array();
var sort = new Array();

var result = $("#hid_log_data").html();
var employeeApp = angular.module("EmployeeApp",[]);
var offset = 0, last_offset = "-", limit_row = 100;
var q = $("#input_search").val(), username = $("#username").val(), role = $("#role").val(), 
activity = $("#activity").val(), detail = $("#detail").val();
var start_date = $("#start_date").val();
var end_date = $("#end_date").val();
employeeApp.controller("empCtrl",function($scope, $http){
	$scope.query = {}
	$scope.queryBy = '$'
	$scope.employees = $.parseJSON(result);
	console.log(result);
	$scope.orderProp="logID";   
	$scope.predicate = 'logID'
	$scope.Range = ""
	$scope.start_date = ""
	$scope.end_date = ""
	$scope.loading = false
	$scope.queryFilter = function(data)
	{
		var now = new Date();
		var date = new Date(data.created_date.replace(/-/g,"/"));
		var start_date = new Date($scope.start_date.replace(/-/g,"/"));
		var end_date = new Date($scope.end_date.replace(/-/g,"/"));
		var min = Math.round((now - date)/( 24 * 3600 * 1000));
		
		if ($scope.start_date != "" && $scope.end_date != "") {
			return (date >= start_date && date <= end_date) ? true : false;
		} else if ($scope.Range == "" ) {
			return true;
		} else if (min <= parseInt($scope.Range)) {
			return true;
		} else {
			return false;
		}
	};
	
	$scope.applyDate = function($event)
	{	
		$scope.end_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker2").datepicker("getDate"));
		$scope.start_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker1").datepicker("getDate"));
		$('.date_dropdown').slideUp("fast");
		console.log($scope.end_date);
		console.log($scope.start_date);
	}
	
	$scope.loadMore = function(offset)
	{		
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/log/getlog",
								data: $.param({"offset" : offset, "q" : q, "username" : username, "role" : role, 
												"activity" : activity, "detail" : detail, "start_date" : start_date,
												"end_date" : end_date}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.employees.length <= 0) {
				$scope.employees = data;
			} else {
				$scope.employees.push.apply($scope.employees,data);		
			}
			
			$scope.loading = (data.length > 0) ? false : true;
			offset += data.length;
			last_offset = data.length;
			console.log();
			$(".bg-loading").hide();
			console.log(data);
			// reduce width of last column by width of scrollbar
			var $elem = $("#log_table");
			var scrollBarWidth = $elem.find('thead').width() - $elem.find('tbody')[0].clientWidth;
			console.log(scrollBarWidth);
			if (scrollBarWidth > 0) {
				// for some reason trimming the width by 2px lines everything up better
				scrollBarWidth -= 2;
				$elem.find('tbody tr:first td:last-child').each(function (i, elem) {
					$(elem).width($(elem).width() - scrollBarWidth);
				});
			}
		});

		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
		});
	};
	
});

employeeApp.filter("asDate", function () {
    return function (input) {
        return new Date(input.replace(/-/g, "/"));
    }
});	

employeeApp.directive("scroll",[function()
{
	return {
		link : function($scope, element, attrs)
		{
			// ng-repeat delays the actual width of the element.
			// this listens for the change and updates the scroll bar
			function scrollListener() {
			var maxScroll = $("table#log_table tbody")[0].scrollHeight - $("table#log_table tbody").outerHeight() - 100;
			if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0)
			{
				offset = $("table#log_table tbody tr").length;
				last_offset = (last_offset == "-") ? offset : last_offset;
				if ($scope.loading == false && last_offset >= limit_row)
				{			
					$scope.loadMore(offset);
					$scope.loading = true;
				}
			}

			console.log($(element).scrollTop() + " " + maxScroll + " " + offset);
			}

			// so that when you go to a new link it stops listening
			element.on('remove', function() {
			clearInterval(listener);
			});

			var listener = setInterval(function() {
			scrollListener();
			}, 650);
		}
	}
}]);

$(function () {
	var data, username, created_date, activity_name, total_data, daily_activity;
});

$(document).ready(function(e)
{
	$("#export").click(function(e)
	{
		$(".bg-log-loading").show();
		$.ajax({
			url : base_url + "/log/logcsv",
			dataType : "html",
			type : "GET",
			success : function(result)
			{
				$(".bg-log-loading").hide();
				console.log(result);
				window.location = base_url + "/" + result;
			}
		});
	});
});

$(document).ready(function(){
	$(".bg-log-loading").hide();
	$("#advanced_search_container").hide();
	$("#advanced_search_button").click(function(){
		if(pernah && !hilang){
		$("#advanced_search_container").slideUp();
			// $("#input_search").removeAttr("disabled");
			// $("#search_button").removeAttr("disabled");
			$(".adv-search").removeClass("up");
			$(".adv-search").addClass("down");
			hilang=true;
		}
		else{
			$("#advanced_search_container").slideDown();
			// $("#input_search").attr("disabled","disabled");
			// $("#search_button").attr("disabled","disabled");
			$(".adv-search").removeClass("down");
			$(".adv-search").addClass("up");
			hilang=false;
		}
		pernah=true;
	});
	$( "#datepicker1" ).datepicker({maxDate : "d"});
	
	$("#datepicker2").datepicker({
		maxDate: "d" ,
		onSelect: function(value, date) {
		$( "#datepicker1" ).datepicker( "option", "maxDate", value );
	}
	});
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	
	$( "#datepicker2" ).datepicker();
	$(".datepick").datepicker();
	$(".bg-loading").hide();
		
	$(".filter_button").click(function(e)
	{
		$(".filter_button").removeClass("active");
		$(this).addClass("active");
	});
	
	$("#start_date_btn").click(function()
	{
		$('.date_dropdown').slideToggle();
	});
});
