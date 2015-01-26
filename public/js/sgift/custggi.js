var hilang =false;
var pernah=false;
var data = [];
var trans = [];
var idx = 0;
var page=1;
var flag=0, lock =false;
var offset = 1, last_offset = "-", limit_row = 100;
var start_date='', end_date='';
var q = $("#q").val();
var name = $("#name").val();
var email = $("#email").val();
var phone = $("#phone").val();
var address = $("#address").val();
var holiday = $("#holiday").val();
var device_model = $("#device_model").val();
var imei = $("#imei").val();
var gender = $("#gender").val();
var start_dob = $("#start_dob").val();
var end_dob = $("#end_dob").val();
var start_registered = $("#start_registered").val();
var end_registered = $("#end_registered").val();

var $header = $("#transaction_table > thead").clone();
var $fixedHeader = $("#header-fixed").append($header);
var tableOffset = $("#transaction_table").offset().top;
var app = angular.module('myAppSgift', ['ngSanitize']);

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
	
	$scope.trustHtml = function(input)
	{
		return $sce.trustAsHtml(input);
	};
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/custggi/gettotal",
								data: $.param({"q" : q, "name" : name, "email" : email, "phone" : phone, "gender" : gender, "address" : address, "holiday" : holiday , "start_dob" : start_dob, "end_dob" : end_dob, 
												"start_registered" : start_registered, "end_registered": end_registered, "device_model" : device_model, "imei" : imei}),
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
								url: base_url+"/custggi/loadmore",
								data:  $.param({"page" : page, "q" : q, "name" : name, "email" : email, "phone" : phone, "gender" : gender, "address" : address, "holiday" : holiday , "start_dob" : start_dob, "end_dob" : end_dob, 
												"start_registered" : start_registered, "end_registered": end_registered, "device_model" : device_model, "imei" : imei}),
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
								
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	 };
	 
	 $scope.sort_c_by = function(predicate) {
		 $scope.predicate_c = predicate;
		 $scope.reverse_c = !$scope.reverse_c;
	 };
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/custggi/exportcsv",
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
								url: base_url+"/custggi/exportcsv",
								data: $.param({ "export" : 0, "q" : q, "name" : name, "email" : email, "phone" : phone, "gender" : gender, "address" : address, "holiday" : holiday , "start_dob" : start_dob, "end_dob" : end_dob, 
												"start_registered" : start_registered, "end_registered": end_registered, "device_model" : device_model, "imei" : imei}),
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
	
	$scope.exportAllDevice = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/custggi/exportcsv",
								data: $.param({ "export" : 2, "q" : q, "q" : q, "name" : name, "email" : email, "phone" : phone, "gender" : gender, "address" : address, "holiday" : holiday , "start_dob" : start_dob, "end_dob" : end_dob, 
												"start_registered" : start_registered, "end_registered": end_registered, "device_model" : device_model, "imei" : imei}),
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
			alert(data);
			$(".bg-log-loading").hide();
			console.log("AJAX Error.");
		});
	};
	
	$scope.search_user = function(id){
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/searchuser",
								data: $.param({"user_id" : id }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config)
		{
			$scope.createPanelUser(data[0]);
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("AJAX Error (search email user)." + id);
		});
	}
	
	$scope.createPanelUser = function(data){
			data.user_kid = (data.user_kid == null) ? '-' : data.user_kid;
			data.user_address = (data.user_address == null) ? '-' : data.user_address;
			data.user_province = (data.user_province == null) ? '-' : data.user_province;
			data.first_redeem = (data.first_redeem == null) ? '-' : data.first_redeem;
			data.first_redeem_type = (data.first_redeem_type == null) ? '-' : data.first_redeem_type;
			data.last_redeem = (data.last_redeem == null) ? '-' : data.last_redeem;
			data.last_redeem_type = (data.last_redeem_type == null) ? '-' : data.last_redeem_type;
			$("#detail_user").html(	//gue ganti
			'<div class="form-horizontal">'+
			'<div class="promo_content">'+
				'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">User ID</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_id+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Name</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_name+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				
				'<label class="col-sm-5 control-label" for="inputEmail3">Marital Kid Count</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_kid+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				
				'<label class="col-sm-5 control-label" for="inputEmail3">Address</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+ data.user_address+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Address Province</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_province+'</label>'+
			'</div>' +
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">First Redeem Date</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.first_redeem+'</label>'+
			'</div>'+
			
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">First Redeem Type</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.first_redeem_type+'</label>'+
			'</div>'+
			
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Last Redeem Date</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.last_redeem+'</label>'+
			'</div>'+
			
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Last Redeem Type</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.last_redeem_type+'</label>'+
			'</div>'+
			
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Total Redeem</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.total_redeem_user+'</label>'+
			'</div>'		
		);
	}
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

app.directive('popupUser',function(){ 
	return {
		link: function($scope, elem, attrs){
			elem.bind("click", function() {
				$("#detail_user").html("Loading....");
				$scope.search_user(attrs.userdata);
				$(".cover").show();
				$('.user_detail_container').animate({
					opacity: 1,
					left: "250",
					height: "show"
				}
				,'fast', function() {
					// this.hide();
				});
			
			});
		}
	};
});

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
	
	$('#close_user').click(function(){
						$(".cover").hide();
						$('.user_detail_container').animate({
						opacity: 1,
						left: "250",
						height: "hide"
						}, 'fast', function() {
							//this.hide();
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