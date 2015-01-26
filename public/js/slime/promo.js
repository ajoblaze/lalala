var hilang =false;
var pernah=false;
var data = [];
var trans = [];
var idx = 0;
var page=1;
var flag=0, lock =false;
var offset = 1, last_offset = "-", limit_row = 100;
var start_date='', end_date='';
var q = $("#hid_param_q").val();
var promo_name = $("#promo_name").val();
var start_start_date = $("#start_start_date").val();
var end_start_date = $("#end_start_date").val();
var start_end_date = $("#start_end_date").val();
var end_end_date = $("#end_end_date").val();
var start_valid_date = $("#start_valid_date").val();
var end_valid_date = $("#end_valid_date").val();

var $header = $("#transaction_table > thead").clone();
var $fixedHeader = $("#header-fixed").append($header);
var tableOffset = $("#transaction_table").offset().top;
var app = angular.module('myAppSlime', ['ngSanitize']);

app.filter('startFrom', function() {
	return function(input, start) {
	if(input) {
	start = +start; //parse to int
	return input.slice(start);
	}
	return [];
	}
});

app.controller('searchCrtl', function ($scope, $http, $sce, $filter) {
	$scope.query = {}
	$scope.queryBy = "$"
	$scope.predicate_c = "";
	$scope.ids = 0;
	$scope.search_list = $.parseJSON($('#simpan_search').html());
	$scope.predicate = '-x';
	$scope.DateRange = "";
	$scope.loading = false;
	$scope.totalData = 0;
	$scope.deviceData = new Array();
	$scope.downloadData = new Array();
	$scope.dateBy = 'redeem_time';
	$scope.start_date = '';
	$scope.end_date = '';
	$scope.ff = $('.t-c').scroll(function(){
		if($(this).scrollTop() == $(this)[0].scrollHeight - $(this).height() ){
			last_offset = (last_offset == "-") ? $scope.search_list.length : last_offset;
			console.log(offset + ", " + last_offset);
			if (last_offset >= limit_row) {
				offset=offset+1;
				$scope.loading = true;
				$scope.loadMore(offset);
			}
		}
	});
	
	$scope.trustHtml = function(input)
	{
		return $sce.trustAsHtml(input);
	};
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/promo/gettotalpromo",
								data: $.param({"q" : q, "promo_name" : promo_name, "start_start_date" : start_start_date, "end_start_date" : end_start_date, 
												"start_end_date" : start_end_date, "end_end_date": end_end_date, "start_valid_date":start_valid_date, "end_valid_date" : end_valid_date}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.loadMore = function(page)
	{		
		lock = true;
		$(".bg-loading").show()
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/promo/loadmorepromo",
								data:  $.param({"page" : page, "q" : q, "promo_name" : promo_name, "start_start_date" : start_start_date, "start_end_date" : start_end_date, 
												"end_start_date" : end_start_date, "end_end_date": end_end_date, "start_valid_date":start_valid_date, "end_valid_date" : end_valid_date}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			
			if ($scope.search_list.length <= 0) {
				$scope.search_list = data;
			} else {
				$scope.search_list.push.apply($scope.search_list,data);		
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
	
	$scope.showDevice = function($event, data)
	{
		$(".pname").html(data.promo_name);
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/promo/getpromodevice",
								data:  $.param({"promo_id" : data.id}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config) {		
			console.log(data);
			$scope.deviceData = data;
			$("#deviceBox").modal('toggle');
			$(".bg-log-loading").hide();
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
			$(".bg-log-loading").hide();
		});	
	};
	
	$scope.showProduct = function($event, data)
	{
		$(".pname").html(data.promo_name);
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/promo/getpromoproduct",
								data:  $.param({"promo_id" : data.id}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config) {		
			console.log(data);
			$scope.productData = data;
			$("#productBox").modal('toggle');
			$(".bg-log-loading").hide();
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
			$(".bg-log-loading").hide();
		});	
	};
								
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	 };
	 
	 $scope.sort_c_by = function(predicate) {
		 $scope.predicate_c = predicate;
		 $scope.reverse_c = !$scope.reverse_c;
	 };
	 
	$scope.queryByDay = function (data){
		var now = new Date();
		var date = new Date(data.valid_date.replace(/-/g,"/"));		
		var minimum = Math.round((now - date)/( 24 * 3600 * 1000));
		
		console.log("x");
		if ($scope.DateRange == "" ) {
			return true;
		} else if (minimum <= parseInt($scope.DateRange)) {
			return true;
		}
		
		return false;
	};
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		data = $filter('filter')(data, $scope.queryByDay);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/promo/exportcsv",
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
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/promo/exportcsv",
								data: $.param({ "q" : q, "promo_name" : promo_name, "start_start_date" : start_start_date, "start_end_date" : start_end_date, 
												"end_start_date" : end_start_date, "end_end_date": end_end_date, "start_valid_date":start_valid_date, "end_valid_date" : end_valid_date}),
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
					
app.directive('tesHover', function(){
	return {
		link: function($scope, elem, attrs){
			elem.popover({placement:'left', html:true,title:"Claim Detail", content:$scope.detail_store(attrs.data-1, 'd'), trigger:'hover'});
		}
	};

});

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

$(document).ready(function(){
	
	$(".bg-log-loading").hide();
	$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	angular.element($("body")).scope().loadTotal();
	
	$( "#datepicker" ).datepicker();
	$('#datepicker2').removeClass('hasDatepicker');

	$(document).mouseup(function (e)
	{
    var container = $(".promo_detail_container");
	var container2 = $(".user_detail_container");

	if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.animate({
						opacity: 1,
						left: "250",
						height: "hide"
						}, 'fast', function() {
							//this.hide();
						});
    }
	
	 if (!container2.is(e.target) // if the target of the click isn't the container...
        && container2.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container2.animate({
						opacity: 1,
						left: "250",
						height: "hide"
						}, 'fast', function() {
							//this.hide();
						});
    }
	
	
	
	$(".cover").click(function(e)
	{
		$(".cover").hide();
        container.animate({
						opacity: 1,
						left: "250",
						height: "hide"
						}, 'fast', function() {
							//this.hide();
						});
	});
	
});
	$("#datepicker2").datepicker({
		maxDate: "-0d" ,
		onSelect: function(value, date) {
		$( "#datepicker1" ).datepicker( "option", "maxDate", value );
	}
	});
	$("#start_date_btn").click(function(){
		$('.date_dropdown').slideToggle();
		//$("#datepicker2").datepicker("show"));
	});
	
	

$('.filter_button').click(function(){
	$('button.filter_button').removeClass('active');
	$(this).addClass('active');
	start_date=end_date = '';
});
$(".bg-loading").hide();
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

		//$("#header-fixed").css({'top':tableOffset});
		function adjustTable(){
					var $table = $('table#transaction_table'),
						$bodyCells = $table.find('thead tr:first').children(),
						colWidth;
					colWidth = $bodyCells.map(function() {
					//	alert($(this).width());
						return $(this).width();
					}).get();
					colHeight = $bodyCells.map(function() {
					//	alert($(this).width());
						return $(this).height();
					}).get();
					$('#header-fixed').find('thead tr').children().each(function(i, v) {
						//alert('sa'+colWidth[i]);
						
						$(v).width(colHeight[i]);
						$(v).width(colWidth[i]);
					}); 
				}
				adjustTable();				
				$(window).resize(function(){
					var $table = $('table#transaction_table'),
						$bodyCells = $table.find('thead tr:first').children(),
						colWidth;
					colWidth = $bodyCells.map(function() {
					//	alert($(this).width());
						return $(this).width();
					}).get();
					$('#header-fixed').find('thead tr').children().each(function(i, v) {
						//alert('sa'+colWidth[i]);
						$(v).width(colWidth[i]);
					}); 
				});
			$('.t-c').scroll( function() {
			var tableOffset = $(this).scrollTop();
			$("#header-fixed").css({'top':tableOffset});
			});
			$('#search-form').submit(function(){
			var q;
			q = $(this).serialize();
						//alert(q);
				})
						
	
		function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}		

					function detail_store(idx, claim_time){
						var str="";
						if(claim_time == null)
						{
						
						}
						else{
						str+= "Store ID : "+trans[idx]["outlet_id"]+"<br/>"; 
						str+= "Store Name : "+trans[idx]["outlet_name"]+"<br/>";
						str+= "Store Address : "+trans[idx]["outlet_address"]+"<br/>";
						return str;
						}
					}
					
					$(document).on('mouseover', 'td.hover_table', function(){
						
						var index = $(this).attr('data');
							//alert(index);
					});
			});