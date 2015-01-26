var selects = document.getElementById("redeem");
var hilang =false;
var pernah=false;
var offset = 0, last_offset = "-", limit_row=100;
var app = angular.module('myAppSgift', ['ngSanitize']);
var start_date='', end_date='';
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
	$scope.query = {};
	$scope.queryBy = "$";
	$scope.search_list =$.parseJSON( $('#hid_merchant_promo').html() );
	$scope.predicate = '';
	$scope.reverse = true;
	$scope.promo_name='';
	$scope.loading = false;
	$scope.totalData = 0;
	$scope.DateRange = "";
	$scope.dateBy = "start_date";
	
	$scope.sort_by = function(predicate) {
		$scope.predicate = predicate;
		$scope.reverse = !$scope.reverse;
	};
	
	$scope.trustHtml = function(input) {
		return $sce.trustAsHtml(input);
	};
	
	$scope.loadMore = function(offset)
	{		
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/loadmorepromo",
								data: $.param({
									"offset" : offset,
									"q" : $("#input_search").val(),
									"coupon_title" : $("#coupon_title").val(), 
									"merchant" : $("#merchant").val(), 
									"type" : $("#type").val(), 
									"publish_date" : $("#publish_date").val(), 
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
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/gettotalpromo",
								data: $.param({"q" : $("#input_search").val(),
												"coupon_title" : $("#coupon_title").val(), 
												"merchant" : $("#merchant").val(), 
												"type" : $("#type").val(), 
												"publish_date" : $("#publish_date").val() }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.queryByDay = function (data){
		var now = new Date();
		var date ;

		if($scope.dateBy=='start_date'){
			date= new Date(data.start_date.replace(/-/g, "/"));
		}
		else if($scope.dateBy=='end_date'){
			date= new Date(data.end_date.replace(/-/g, "/"));
		}
		else if($scope.dateBy=='publish_date'){
			date= new Date(data.publish_date.replace(/-/g, "/"));
		}
		
		var minimum = Math.round((now - date)/( 24 * 3600 * 1000));
		
		if ($scope.DateRange == "" ) {
			return true;
		} else if (minimum <= parseInt($scope.DateRange)) {
			return true;
		}
		
		return false;
	};
	
	$scope.queryByPickerDay = function (data){
		if(start_date=='' || end_date=='')return true;
		var date ;
		
		
		if($scope.dateBy=='start_date'){
			date= new Date(data.start_date.replace(/-/g, "/"));
		}
		else if($scope.dateBy=='end_date'){
			date= new Date(data.end_date.replace(/-/g, "/"));
		}
		else if($scope.dateBy=='publish_date'){
			date= new Date(data.publish_date.replace(/-/g, "/"));
		}
		
		date= Date.parse(date);
		
		if(date >= start_date && date <= end_date){
			return true;
		}
		return false;
		
	}
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		data = $filter('filter')(data, $scope.queryByDay);
		data = $filter('filter')(data, $scope.queryByPickerDay);
		
		var json = JSON.stringify(data);
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/exportcsvpromo",
								data: $.param({"export" : 1, "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("exportCurrent Error. "  + data);
		});
	};
	
	$scope.exportAll = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/exportcsvpromo",
								data: $.param({
									"export" : 0,
									"q" : $("#input_search").val(),
									"coupon_title" : $("#coupon_title").val(), 
									"merchant" : $("#merchant").val(), 
									"type" : $("#type").val(), 
									"publish_date" : $("#publish_date").val(), 
								}),
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
	$scope.search_promo = function(promo_id,batch_id,campaign_id,provider_id,channel_id){

		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/searchpromodetail",
								data: $.param({"promo_id" : promo_id, "batch_id" : batch_id ,"campaign_id" : campaign_id, "provider_id" : provider_id,"channel_id" : channel_id}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config)
		{
		console.log(data);
			var str='';
			var comma='';
			var data_temp = [];
			var temp_pm='';
			var count=0;
			data_temp = data[0];
			//alert(data.length);
			for(x in data)
			{
				if(data[x]['pm_name'] != temp_pm){
					str+=comma+data[x]['pm_name'];
					comma=', ';
					count++;
				}
				temp_pm= data[x]['pm_name'];
			}
			data_temp['pm_name']=str;
			str='';
			comma='';			
			if(data[0]['product_name'] == null)
			{
				data_temp['product_name']='no data';
			}
			else
			{
				
				for(var x =0 ;x< (data.length/count) ;x++)
				{
						str+=comma+data[x]['product_name'];
						comma=', ';
				}
				data_temp['product_name']=str;
				
				//alert(data_temp.length);
			}
			
			str='';
			$scope.createPanelPromo(data_temp);
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log('gagal calvin');
			console.log("AJAX Error (search promo)." + data);
		});
	}	
	$scope.createPanelPromo = function(data){
		$("#detail_promo").html(	
		'<div class="form-horizontal">'+
		'<div class="promo_content">'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Promo ID</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.channel+'</label>'+
		'</div>'+
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Merchant Name</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.merchant_name+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Coupon title</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.coupon_title+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Promo Image</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+"<img src='http://117.102.247.5/static/public/"+data.promo_image+"'/>"+'</label>'+
		'</div>'+
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Type</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.claim_type+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Coupon Description</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.description+'</label>'+
		'</div>'+
		
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Terms & Conditions</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.tnc+'</label>'+
		'</div>'
		
		
		);
		
	};
});

app.filter("asDate", function()
{
	return function(input)
	{
		return new Date(input.replace(/-/g, "/"));
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
app.directive('tesPopup',function(){
	return {
		link: function($scope, elem, attrs){
			elem.bind("click", function() {
				$("#promo_number tr").html("");
				$("#detail_promo").html("Loading....");
				var data = attrs.promodata.split('-');
				// alert("promoID:"+data[2]+" batch_id:"+ data[3]+" cam_id:"+ data[4]+" prov_id"+ data[1]+" ch_id:"+ data[0]);
				$scope.search_promo(data[2], data[3], data[4], data[1], data[0]);
				$(".cover").show();
				$('.promo_detail_container').animate({
						opacity: 1,
						left: "250",
						height: "show"
						}, 'fast', function() {
							//this.hide();
						});
					
			} );
		}
	};
});
$('.filter_button').click(function(){
	$('button.filter_button').removeClass('active');
	$(this).addClass('active');
	start_date=end_date = '';
});

$("#start_date_btn").click(function(){
	$('.date_dropdown').slideToggle();
});

$("#apply_date").click(function(){
	$('button.filter_button').removeClass('active');
	$('.date_dropdown').slideUp();
	app.DateRange = "";
	end_date = $.datepicker.formatDate( "yy-mm-dd 23:59:59", $("#datepicker2").datepicker("getDate"));
	start_date = $.datepicker.formatDate( "yy-mm-dd 00:00:00", $("#datepicker1").datepicker("getDate"));
	start_date = Date.parse(start_date.replace(/-/g,"/"));
	end_date = Date.parse(end_date.replace(/-/g,"/"));
});

$(document).ready(function(){
	$('#redeem').change(function() {
		if(selects.options[selects.selectedIndex].value == "start_date") {
			//alert("start");
			document.getElementById("time_filter").className = "btn btn-success";
			document.getElementById("start_date_btn").className = "btn btn-success";
			document.getElementById("redeem/claim").className = "form-group has-success";
			document.getElementById("apply_date").className = "btn btn-success pull-right";
		} else if(selects.options[selects.selectedIndex].value == "end_date") {
			//alert("end");
			document.getElementById("time_filter").className = "btn btn-danger";
			document.getElementById("start_date_btn").className = "btn btn-danger";
			document.getElementById("redeem/claim").className = "form-group has-error";
			document.getElementById("apply_date").className = "btn btn-danger pull-right";
		} else if(selects.options[selects.selectedIndex].value == "publish_date") {
			//alert("publish");
			document.getElementById("time_filter").className = "btn btn-warning";
			document.getElementById("start_date_btn").className = "btn btn-warning";
			document.getElementById("redeem/claim").className = "form-group has-warning";
			document.getElementById("apply_date").className = "btn btn-warning pull-right";
		}
	});	
	$('#close_promo').click(function(){
				$(".cover").hide();
						$('.promo_detail_container').animate({
						opacity: 1,
						left: "250",
						height: "hide"
						}, 'fast', function() {
							//this.hide();
						});
	});
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
	});	
	$(".bg-log-loading").hide();
	$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	$('#datepicker2').removeClass('hasDatepicker');
	$("#datepicker2").datepicker({
		maxDate: "-0d" ,
		onSelect: function(value, date) {
		$( "#datepicker1" ).datepicker( "option", "maxDate", value );
	}
	});
	angular.element($("body")).scope().loadTotal();
	
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
});