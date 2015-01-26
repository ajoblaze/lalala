var app = angular.module('myAppSgift', ['ngSanitize']);
var offset = 0, last_offset = "-", limit_row = 100;
var offset_detail = 0, last_offset_detail = "-", limit_row_detail = 100;
var merchant_id = "";
var page = 0;
app.controller('searchCrtl',['$scope', '$http', '$sce', '$filter', function ($scope, $http, $sce, $filter) {
	$scope.query = {}
	$scope.queryBy = "$"
	$scope.search_list =$.parseJSON($('#hid_merchant_product_amount').html());
	$scope.predicate = '-x';
	$scope.loading = false;
	$scope.isLoading = false;
	$scope.totalData = 0;
	
	$scope.query_detail = {}
	$scope.queryBy_detail = "$"
	$scope.search_list_detail = new Array();
	$scope.predicate_detail = '-x';
	$scope.loading_detail = false;
	$scope.isLoading_detail = false;
	$scope.totalData_detail = 0;
	
	$scope.loadTotalAmount = function()
	{
		var segment = [];
		var i=0;
		
		if($("#segment1").is(":checked")){
			segment[i]=$("#segment1").val();
			i+=1;
		}
		
		if($("#segment2").is(":checked")){
			segment[i]=$("#segment2").val();
			i+=1;
		}
		
		if($("#segment3").is(":checked")){
			segment[i]=$("#segment3").val();
			i+=1;
		}
	
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/getcountamount",
								data: $.param({ "q":$("#input_search").val(), "merchant_name":$("#merchant_name").val(), "segment" : segment }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.trustHtml = function (html)
	{
		return $sce.trustAsHtml(html);
	}
	
	$scope.loadTotalDetail = function(merchant_id)
	{
		var segment = [];
		var i=0;
		
		if($("#segment1_detail").is(":checked")){
			segment[i]=$("#segment1_detail").val();
			i+=1;
		}
		
		if($("#segment2_detail").is(":checked")){
			segment[i]=$("#segment2_detail").val();
			i+=1;
		}
		
		if($("#segment3_detail").is(":checked")){
			segment[i]=$("#segment3_detail").val();
			i+=1;
		}
	
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/getcountdetail",
								data: $.param({ "merchant_id" : merchant_id, "q_detail":$("#input_search_detail").val(),"promotion_name_detail":$("#promo_name_detail").val(), "merchant_name_detail":$("#merchant_name_detail").val(), "start_date":$("#start_date").val(), "end_date":$("#end_date").val(), "segment_detail" : segment}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData_detail = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}

	$scope.loadDetail = function($event, data)
	{
		merchant_id = (data == "") ? merchant_id : data.merchant_id;
		var flg = (data == "") ? 1 : 0;
		var q_detail = $("#input_search_detail").val();
		var promo_name_detail = $("#promo_name_detail").val();
		var merchant_name_detail = $("#merchant_name_detail").val();
		var start_date = $("#start_date").val();
		var end_date = $("#end_date").val();
		var segment = [];
		var i=0;
		
		if($("#segment1_detail").is(":checked")){
			segment[i]=$("#segment1_detail").val();
			i+=1;
		}
		
		if($("#segment2_detail").is(":checked")){
			segment[i]=$("#segment2_detail").val();
			i+=1;
		}
		
		if($("#segment3_detail").is(":checked")){
			segment[i]=$("#segment3_detail").val();
			i+=1;
		}
		
		if (data == "") {
			$(".bg-sub-loading").show();
		} else { 
			$(".bg-log-loading").show();
		}
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/promodetail",
								data: $.param({ "merchant_id" : merchant_id,"q_detail": q_detail, "promotion_name_detail": promo_name_detail, "merchant_name_detail": merchant_name_detail, 
												"start_date" : start_date, "end_date" : end_date, "segment_detail":segment}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$("#div_detail").show();
			if (flg == 1) {
				$(".bg-sub-loading").hide();
			} else { 
				$(".bg-log-loading").hide();
			}
			window.location = "#div_detail";
			$scope.loadTotalDetail(merchant_id);
			$scope.search_list_detail = data;
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
								
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	 };
	 
	$scope.sort_by_detail = function(predicate_detail) {
		 $scope.predicate_detail = predicate_detail;
		 $scope.reverse_detail = !$scope.reverse_detail;
	 };
	 
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		data = $filter('filter')(data, $scope.queryByDay);
		data = $filter('filter')(data, $scope.queryByPickerDay);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/exportcsv",
								data: $.param({"export" : "1", "json" : json}),
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
			console.log("exportCurrent Error. "  + data);
		});
	};
	
	$scope.loadMore = function()
	{		
		var segment = [];
		var i=0;
		
		if($("#segment1").is(":checked")){
			segment[i]=$("#segment1").val();
			i+=1;
		}
		
		if($("#segment2").is(":checked")){
			segment[i]=$("#segment2").val();
			i+=1;
		}
		
		if($("#segment3").is(":checked")){
			segment[i]=$("#segment3").val();
			i+=1;
		}
		
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/loadmore",
								data: $.param({"offset" : $scope.search_list.length, "q":$("#input_search").val(), "merchant_name":$("#merchant_name").val(),segment:segment}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.search_list.length <= 0) {
				$scope.search_list = data;
			} else {
				$scope.search_list.push.apply($scope.search_list,data);		
			}
			$scope.loading = (data.length >= limit_row) ? false : true;
			last_offset = data.length;
			$(".bg-loading").hide();
			$scope.isLoading = false;
		});
			
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
			$scope.isLoading = false;
		});	
	};
	
	$scope.loadMore_detail = function()
	{		
		var segment = [];
		var i=0;
		
		if($("#segment1_detail").is(":checked")){
			segment[i]=$("#segment1_detail").val();
			i+=1;
		}
		
		if($("#segment2_detail").is(":checked")){
			segment[i]=$("#segment2_detail").val();
			i+=1;
		}
		
		if($("#segment3_detail").is(":checked")){
			segment[i]=$("#segment3_detail").val();
			i+=1;
		}
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/loadmoredetail",
								data: $.param({offset : $scope.search_list_detail.length, "q_detail":$("#input_search_detail").val(),"promo_name_detail":$("#promo_name_detail").val(), "merchant_name_detail":$("#merchant_name_detail").val(),segment_detail:segment}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.search_list_detail.length <= 0) {
				$scope.search_list_detail = data;
			} else {
				$scope.search_list_detail.push.apply($scope.search_list_detail,data);		
			}
			$scope.loading_detail = (data.length >= limit_row_detail) ? false : true;
			last_offset_detail = data.length;
			$(".bg-loading").hide();
			$scope.isLoading_detail = false;
		});
			
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
			$scope.isLoading_detail = false;
		});	
	};
	
	$scope.exportAll = function($event)
	{	var segment = [];
		var i=0;
		
		if($("#segment1").is(":checked")){
			segment[i]=$("#segment1").val();
			i+=1;
		}
		
		if($("#segment2").is(":checked")){
			segment[i]=$("#segment2").val();
			i+=1;
		}
		
		if($("#segment3").is(":checked")){
			segment[i]=$("#segment3").val();
			i+=1;
		}
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/exportcsv",
								data: $.param({"export" : "0", "q":$("#input_search").val(), "merchant_name":$("#merchant_name").val(),segment:segment}),
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
			console.log("AJAX Error.");
		});
	};
	
	$scope.exportCurrent_detail = function($event)
	{
		var data = $filter('filter')($scope.search_list_detail, $scope.query_detail);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/productggi/exportcsvdetail",
								data: $.param({"export" : "1", "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-sub-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-sub-loading").hide();
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("exportCurrent Error. "  + data);
		});
	};
}]);
					
app.filter('asDate', function() {
	return function(input) {
		return (input != null ) ? new Date(input.replace(/-/g, "/").replace(".","").replace("+","")) : "";
	}
});

app.directive('scroll', [function() {
  return {
    link: function($scope, element, attrs) {
      // ng-repeat delays the actual width of the element.
      // this listens for the change and updates the scroll bar
      function scrollListener() {
		var maxScroll = $("table#merchant_table1 tbody")[0].scrollHeight - $("table#merchant_table1 tbody").outerHeight() - 100;
		if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0 && $scope.isLoading==false)
		{
			offset = $("table#merchant_table1 tbody tr").length;
			last_offset = (last_offset == "-") ? offset : last_offset;
			console.log(last_offset+" "+limit_row);
			if (last_offset >= limit_row)
			{
				page++;
				$scope.isLoading = true;
				$scope.loadMore(page);
				$scope.loading = true;
			}
			else
			{
				$(".bg-loading").hide();
			}
		}
	  
		var maxScroll_detail = $("table#merchant_table2 tbody")[0].scrollHeight - $("table#merchant_table2 tbody").outerHeight() - 100;
		if ($(element).scrollTop() >= maxScroll_detail && $(element).scrollTop() != 0 && $scope.isLoading_detail==false)
		{
			offset_detail = $("table#merchant_table2 tbody tr").length;
			last_offset_detail = (last_offset_detail == "-") ? offset_detail : last_offset_detail;
			if (last_offset_detail >= limit_row_detail)
			{
				page++;
				$scope.isLoading_detail = true;
				$scope.loadMore_detail(page);
			}
			else
			{
				$(".bg-loading").hide();
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
	$(".bg-log-loading").hide();
	$(".bg-sub-loading").hide();
	$(".bg-loading").hide();
	angular.element($("#app_top")).scope().loadTotalAmount();
	$("#advanced_search_container").hide();
	$("#advanced_search_button").click(function(){
		$('#advanced_search_container').slideToggle();
	});
	
	$("#advanced_search_container_detail").hide();
	$("#advanced_search_button_detail").click(function(){
		$('#advanced_search_container_detail').slideToggle();
	});
	
});