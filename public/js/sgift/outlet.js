var hilang =false;
var pernah=false;
var page=1;
var page_merchant=1;
var flag=0, lock =false;
var offset = 1, last_offset = "-", limit_row = 100;
var flag_merchant=0, lock_merchant =false;
var offset_merchant = 1, last_offset_merchant = "-", limit_row_merchant = 100;

var q = $("#input_search").val();
var provider_name = $("#provider_name").val();
var promo_id = $("#promo_id").val();
var promo_name = $("#promo_name").val();
var claim_type1 = $("#claim_type1").prop("checked"), claim_type2 = $("#claim_type2").prop("checked");
var merchant_id = $("#merchant_id").val();
var merchant_name = $("#merchant_name").val();
var outlet_id = $("#outlet_id").val();
var outlet_name = $("#outlet_name").val();
var start_time = $("#start_time").val();
var end_time = $("#end_time").val();

var q_merchant = $("#input_search_merchant").val();
var merchant_id_merchant = $("#merchant_id_merchant").val();
var merchant_name_merchant = $("#merchant_name_merchant").val();
var outlet_id_merchant = $("#outlet_id_merchant").val();
var outlet_name_merchant = $("#outlet_name_merchant").val();
var city_merchant = $("#city_merchant").val();

var app = angular.module('myOutlet', ['ngSanitize']);

app.controller('searchCrtl', function ($scope, $sce, $filter, $http) {
	$scope.query = {}
	$scope.queryBy = "$"
	$scope.query_detail = {}
	$scope.queryBy_detail = "$"
	$scope.totalPromo = 0;
	$scope.totalMerchant = 0;
	$scope.search_list_promo = $.parseJSON($('#simpan_search').html());
	$scope.search_list_merchant = $.parseJSON($('#simpan_search_merchant').html());
	
	$scope.trustHtml = function(input)
	{
		return $sce.trustAsHtml(input);
	};
	
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	};
	
	$scope.sort_by_detail = function(predicate) {
		 $scope.predicate_detail = predicate;
		 $scope.reverse_detail = !$scope.reverse_detail;
	};
	
	$scope.loadTotalOutlet = function()
	{
		var claim1 = (claim_type1 == true) ? 1 : 0;
		var claim2 = (claim_type2 == true) ? 1 : 0;
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/gettotaloutlet",
								data:  $.param({"page" : page, "q" : q, "provider_name" : provider_name, "promo_id" : promo_id, "promo_name" : promo_name, 
												"claim_type1" : claim1, "claim_type2" : claim2, "merchant_id" : merchant_id, "merchant_name": merchant_name, "outlet_id" : outlet_id,
												"outlet_name" : outlet_name, "start_time" : start_time, "end_time" : end_time}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalPromo = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.loadTotalMerchant = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/gettotalmerchant",
								data:  $.param({"page" : page_merchant, "q_merchant" : q_merchant, "merchant_id_merchant" : merchant_id_merchant, 
												"merchant_name_merchant" : 	merchant_name_merchant, "outlet_id_merchant" : outlet_id_merchant, 
												"outlet_name_merchant" : outlet_name_merchant, "city_merchant" : city_merchant}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalMerchant = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.loadMore = function(page) {		
		lock = true;
		var claim1 = (claim_type1 == true) ? 1 : 0;
		var claim2 = (claim_type2 == true) ? 1 : 0;
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/loadmoreoutlet",
								data:  $.param({"page" : page, "q" : q, "provider_name" : provider_name, "promo_id" : promo_id, "promo_name" : promo_name, 
												"claim_type1" : claim1, "claim_type2" : claim2, "merchant_id" : merchant_id, "merchant_name": merchant_name, "outlet_id" : outlet_id,
												"outlet_name" : outlet_name, "start_time" : start_time, "end_time" : end_time}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.search_list_promo.length <= 0) {
				$scope.search_list_promo = data;
			} else {
				$scope.search_list_promo.push.apply($scope.search_list_promo,data);		
			}
			$scope.loading = (data.length > 0) ? false : true;
			last_offset = data.length;
			$(".bg-loading").hide();
			lock = false;
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
		});	
	};
	
	$scope.loadMoreMerchant = function(page) {		
		lock_merchant = true;
		$(".bg-loading").show()
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/loadmoremerchant",
								data:  $.param({"page" : page_merchant, "q_merchant" : q_merchant, "merchant_id_merchant" : merchant_id_merchant, 
												"merchant_name_merchant" : 		 merchant_name_merchant, "outlet_id_merchant" : outlet_id_merchant, 
												"outlet_name_merchant" : outlet_name_merchant, "city_merchant" : city_merchant}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.search_list_merchant.length <= 0) {
				$scope.search_list_merchant = data;
			} else {
				$scope.search_list_merchant.push.apply($scope.search_list_merchant,data);		
			}
			$scope.loading = (data.length > 0) ? false : true;
			last_offset_merchant = data.length;
			$(".bg-loading").hide();
			lock_merchant = false;
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
		});	
	};
	
	// -----------------------------------------------------------------------------------------------
	// EXPORT PROMO
	// -----------------------------------------------------------------------------------------------
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/exportcsv",
								data: $.param({"export" : 1, "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log("exportCurrent Error. "  + data);
		});
	};
	
	$scope.exportAll = function($event)
	{
		var claim1 = (claim_type1 == true) ? 1 : 0;
		var claim2 = (claim_type2 == true) ? 1 : 0;
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/exportcsv",
								data: $.param({ "export" : 0, "q" : q, "provider_name" : provider_name, "promo_id" : promo_id, "promo_name" : promo_name, 
												"claim_type1" : claim1, "claim_type2" : claim2 ,"merchant_id" : merchant_id, "merchant_name": merchant_name, "outlet_id" : outlet_id,
												"outlet_name" : outlet_name, "start_time" : start_time, "end_time" : end_time}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			
			$(".bg-log-loading").hide();
			console.log("AJAX Error.");
		});
	};
	
	// -----------------------------------------------------------------------------------------------
	// EXPORT MERCHANT
	// -----------------------------------------------------------------------------------------------
	
	
	$scope.exportCurrentMerchant = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/exportcsvmerchant",
								data: $.param({"export" : 1, "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log("exportCurrent Error. "  + data);
		});
	};
	
	$scope.exportAllMerchant = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/outlet/exportcsvmerchant",
								data: $.param({ "export" : 0, "q_merchant" : q_merchant, "merchant_id_merchant" : merchant_id_merchant, 
												"merchant_name_merchant" : 	merchant_name_merchant, "outlet_id_merchant" : outlet_id_merchant, 
												"outlet_name_merchant" : outlet_name_merchant, "city_merchant" : city_merchant}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			
			$(".bg-log-loading").hide();
			console.log("AJAX Error.");
		});
	};
});

app.filter('asDate', function() {
	return function(input) {
		return (input != null ) ? new Date(input.replace(/-/g, "/")) : "";
	}
});

app.directive('scroll', [function() {
  return {
    link: function($scope, element, attrs) {
      // ng-repeat delays the actual width of the element.
      // this listens for the change and updates the scroll bar
      function scrollListener() {
		var maxScroll = $("table#transaction_table tbody")[0].scrollHeight - $("table#transaction_table tbody").outerHeight() - 100;
		if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0)
		{
			offset = $("table#transaction_table tbody tr").length;
			last_offset = (last_offset == "-") ? offset : last_offset;
			if (last_offset >= limit_row && lock == false)
			{
				page++;
				$scope.loadMore(page);
				$scope.loading = true;
			}
			else
			{
				$(".bg-loading").hide();
			}
		}
		
		console.log($(element).scrollTop() + " " + maxScroll + " " + offset + " " + last_offset);
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

app.directive('scroll2', [function() {
  return {
    link: function($scope, element, attrs) {
      // ng-repeat delays the actual width of the element.
      // this listens for the change and updates the scroll bar
      function scrollListener() {
		var maxScroll_merchant = $("table.merchant_table tbody")[0].scrollHeight - $("table.merchant_table tbody").outerHeight() - 100;
		if ($(element).scrollTop() >= maxScroll_merchant && $(element).scrollTop() != 0)
		{
			offset_merchant = $("table.merchant_table tbody tr").length;
			last_offset_merchant = (last_offset_merchant == "-") ? offset_merchant : last_offset_merchant;
			if (last_offset_merchant >= limit_row_merchant && lock_merchant == false)
			{
				page_merchant++;
				$scope.loadMoreMerchant(page_merchant);
				$scope.loading = true;
			}
			else
			{
				$(".bg-loading").hide();
			}
		}
		console.log($(element).scrollTop() + " " + maxScroll_merchant + " " + offset_merchant + " " + last_offset_merchant);
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
	angular.element($("body")).scope().loadTotalOutlet();
	angular.element($("body")).scope().loadTotalMerchant();
	$(".bg-log-loading").hide();
	$(".bg-loading").hide();
	$("#advanced_search_container").hide();
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
	
	$("#advanced_search_container_merchant").hide();
	$("#advanced_search_button_merchant").click(function(){
		if(pernah && !hilang){
			$("#advanced_search_container_merchant").slideUp();
			$(".adv-search").removeClass("up");
			$(".adv-search").addClass("down");
			hilang=true;
		}
		else{
			$("#advanced_search_container_merchant").slideDown();
			$(".adv-search").removeClass("down");
			$(".adv-search").addClass("up");
			hilang=false;
		}
		pernah=true;
	});
});