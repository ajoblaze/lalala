var hilang =false;
var pernah=false;
var app = angular.module('server_claim_log', ['ngSanitize']);
var offset = 100, last_offset = "-", limit_row=100;
var q = $("#input_search").val();
var email = $("#email").val();
var imei = $("#imei_or_mac").val();
var merchant = $("#merchant").val();
var model = $("#model").val();
var promo_name = $("#promo_name").val();
var status = $("#status").val();
var redeem_code = $("#redeem_code").val();
var status_code = $("#status_code").val();
var start_claim = $("#start_claim").val();
var end_claim = $("#end_claim").val();

app.controller('claim_logCrtl', function ($scope, $http, $sce, $filter) {

	$scope.search_list =$.parseJSON( $('#hid_claim_log').html() );
	$scope.loading = false;
	$scope.query = {};
	$scope.queryBy = "$";
	$scope.totalData = 0;
	$scope.start_date = "";
	$scope.end_date = "";
	$scope.Range="";
	$scope.predicate = '';
	$scope.reverse = true;
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		data = $filter('filter')(data, $scope.queryFilter);
		
		var json = JSON.stringify(data);
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/serverlog/exportcsvclaimlog",
								data: $.param({"export" : 1, "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			window.location = base_url + "/" + data;
			$(".bg-log-loading").hide();
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("exportCurrent Error. "  + data);
			$(".bg-log-loading").hide();
		});
	};
	
	$scope.trustHtml = function(input)
	{
		return $sce.trustAsHtml(input);
	};
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/serverlog/gettotalclaim",
								data: $.param({"q" : q, "email" : email, "imei" : imei, "merchant" : merchant, "model" : model, "promo_name":promo_name, "redeem_code" : redeem_code , "status":status, "status_code" : status_code, "start_claim" : start_claim, "end_claim" : end_claim}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			console.log(data);
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.exportTop1000 = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/serverlog/exportcsvclaimlog",
								data: $.param({
									"export" : 0,
									"q" : $("#input_search").val(),
									"email" : $("#email").val(), 
									"imei_or_mac" : $("#imei_or_mac").val(), 
									"merchant" : $("#merchant").val(), 
									"model" : $("#model").val(), 
									"promo_name" : $("#promo_name").val(),
									"redeem_code" : $("#redeem_code").val(),
									"status" : $("#status").val(),
									"status_code" : $("#status_code").val(),
									"start_claim" : $("#start_claim").val(),
									"end_claim" : $("#end_claim").val()
								}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			console.log(data);
			window.location = base_url + "/" + data;
			$(".bg-log-loading").hide();
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("AJAX Error.");
			$(".bg-log-loading").hide();
		});
	};
	
	$scope.sort_by = function(predicate) {
		$scope.predicate = predicate;
		$scope.reverse = !$scope.reverse;
	};
	
	$scope.applyDate = function($event)
	{	
		$scope.end_date = $.datepicker.formatDate( "yy-mm-dd 23:59:59", $("#datepicker2").datepicker("getDate"));
		$scope.start_date = $.datepicker.formatDate( "yy-mm-dd 00:00:00", $("#datepicker1").datepicker("getDate"));
		$('.date_dropdown').slideUp("fast");
	}
	
	$scope.queryFilter = function(data)
	{
		var now = new Date();
		var date = new Date(data.claim_time.replace(/-/g, "/"));
		var start_date = new Date($scope.start_date.replace(/-/g, "/"));
		var end_date = new Date($scope.end_date.replace(/-/g, "/"));
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
	
	$scope.loadMore = function(offset)
	{		
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/serverlog/claimloadmore",
								data: $.param({
									"offset" : offset,
									"q" : $("#input_search").val(),
									"email" : $("#email").val(), 
									"imei_or_mac" : $("#imei_or_mac").val(), 
									"merchant" : $("#merchant").val(), 
									"model" : $("#model").val(), 
									"promo_name" : $("#promo_name").val(),
									"redeem_code" : $("#redeem_code").val(),
									"status" : $("#status").val(),
									"status_code" : $("#status_code").val(),
									"start_claim" : $("#start_claim").val(),
									"end_claim" : $("#end_claim").val()
								}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.search_list.length <= 0) {
				$scope.search_list = data;
			} else {
				$scope.search_list.push.apply($scope.search_list,data);		
			}
			
			$scope.loading = (data.length > 0) ? false : true;
			offset += data.length;
			last_offset = data.length;
			$(".bg-loading").hide();
			// reduce width of last column by width of scrollbar
			var $elem = $("#transaction_table");
			var scrollBarWidth = $elem.find('thead').width() - $elem.find('tbody')[0].clientWidth;
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

app.filter("asDate", function () {
    return function (input) {
        return (input != "") ? new Date(input.replace(/-/g, "/")) : input;
    }
});

app.directive("scroll",[function()
{
	return {
		link : function($scope, element, attrs)
		{
			// ng-repeat delays the actual width of the element.
			// this listens for the change and updates the scroll bar
			function scrollListener() {
				var maxScroll = $("table#transaction_table tbody")[0].scrollHeight - $("table#transaction_table tbody").outerHeight() - 100;
				if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0)
				{
					offset = $("table#transaction_table tbody tr").length;
					last_offset = (last_offset == "-") ? offset : last_offset;
					if ($scope.loading == false && last_offset >= limit_row)
					{
						$scope.loadMore(offset);
						$scope.loading = true;
					}
				}
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


$(document).ready(function(){

	$(".bg-loading").hide();
	$(".bg-log-loading").hide();
	angular.element($("body")).scope().loadTotal();
	$("#advanced_search_container").hide();
	$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	$( "#datepicker2" ).datepicker({ maxDate: "-0d"});
	$("#advanced_search_button").click(function(){
		if(pernah && !hilang){
			$("#advanced_search_container").slideUp();
			$(".adv-search").removeClass("up");
			$(".adv-search").addClass("down");
			hilang=true;
		}
		else{
			$("#advanced_search_container").slideDown();
			$(".adv-search").removeClass("down");
			$(".adv-search").addClass("up");
			hilang=false;
		}
		pernah=true;
	});
	$("#start_date_btn").click(function()
	{
		$('.date_dropdown').slideToggle();
	});
});