var hilang =false;
var pernah=false;
var data = [];
var trans = [];
var idx = 0;
var page=1;
var flag=0
var offset = 1, last_offset = "-", limit_row = 100;
var start_date='', end_date='';
var selects = document.getElementById("redeem");
var q = $("#hid_param_q").val();
var email= $("#hid_param_email").val();
var imei = $("#hid_param_imei").val();
var device = $("#hid_param_device").val();
var telp = $("#hid_param_telp").val();
var store = $("#hid_param_store").val();
var voucher_id = $("#hid_param_voucher_id").val();
var $header = $("#transaction_table > thead").clone();
var $fixedHeader = $("#header-fixed").append($header);
var tableOffset = $("#transaction_table").offset().top;
var start_claim = $("#start_claim").val();
var end_claim = $("#end_claim").val();
var start_redeem = $("#start_redeem").val();
var end_redeem = $("#end_redeem").val();
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

app.controller('searchCrtl',['$scope', '$http', '$sce', '$filter', function ($scope, $http, $sce, $filter) {
	$scope.query = {}
	$scope.queryBy = "$"
	$scope.ids = 0;
	$scope.search_list =$.parseJSON($('#simpan_search').html());
	$scope.predicate = '-x';
	$scope.DateRange = "";
	$scope.loading = false;
	$scope.isLoading = false;
	$scope.totalData = 0;
	$scope.start_date = "";
	$scope.end_date = "";
	$scope.dateBy = 'redeem_time';
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
	}
	
	$scope.loadMore = function(page)
	{		
		$(".bg-loading").show()		
		if ($scope.isLoading == false )
		{
			$scope.isLoading = true;
			var responsePromise = $http({
									method: 'POST',
									url: base_url+"/cssgift/loadmore",
									data: $.param({ "page" : page, "q" : q, "email": email, "imei" : imei, "device" : device, 
											 "telp" : telp, "store" : store, "voucher_id" : voucher_id,
											 "start_redeem": start_redeem, "end_redeem" : end_redeem, "start_claim" : start_claim, "end_claim" : end_claim }),
									headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
			responsePromise.success(function(data, status, headers, config) {
				
				
				if ($scope.search_list.length <= 0) {
					$scope.search_list = data;
				} else {
					$scope.search_list.push.apply($scope.search_list,data);		
				}
				$scope.loading = (data.length > limit_row) ? false : true;
				last_offset = data.length;
				$(".bg-loading").hide();
				$scope.isLoading = false;
			});
			responsePromise.error(function(data,status, headers, config) {
				console.log("AJAX Error.");
				$scope.isLoading = false;
			});	
		}
	};
	
	$scope.loadTotal = function()
	{
		console.log("Imei " + imei);
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/gettotal",
								data: $.param({"q" : q, "email": email, "imei" : imei, "device" : device, 
											   "telp" : telp, "store" : store, "voucher_id" : voucher_id,
											   "start_redeem": start_redeem, "end_redeem" : end_redeem, "start_claim" : start_claim, "end_claim" : end_claim
											   }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
								
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	 };
	 
	$scope.queryByDay = function (data){
		var now = new Date();
		var date ;
		if($scope.dateBy=='redeem_time'){
			date = new Date(data.redeem_time.replace(/-/g,"/"));
		}
		else{
			date = (data.claim_time != null) ? new Date(data.claim_time.replace(/-/g, "/")) : "";
		}

		var minimum = Math.round((now - date)/( 24 * 3600 * 1000));
		
		if ($scope.DateRange == "") {
			console.log($scope.DateRange + " true");
			return true;
		} else if(minimum <= parseInt($scope.DateRange)) {
			return true;
		}
		
		return false;
	};
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		data = $filter('filter')(data, $scope.queryByDay);
		data = $filter('filter')(data, $scope.queryByPickerDay);
		
		var json = JSON.stringify(data);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/exportcsv",
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
			console.log("exportCurrent Error. "  + data);
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
	
	$scope.search_promo = function(promo_id,batch_id,campaign_id,provider_id,channel_id){
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/searchpromo",
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
	
	$scope.exportAll = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/exportcsv",
								data: $.param({"export" : 0, "q" : q, "email" : email , "imei" : imei, "device" : device, "telp" : telp, "store" : store, "voucher_id" : voucher_id}),
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

	$("#apply_date").click(function(){
		$('button.filter_button').removeClass('active');
		$('.date_dropdown').slideUp();
		end_date = $.datepicker.formatDate( "yy-mm-dd 23:59:59", $("#datepicker2").datepicker("getDate"));
		start_date = $.datepicker.formatDate( "yy-mm-dd 00:00:00", $("#datepicker1").datepicker("getDate"));
		$scope.start_date = Date.parse(start_date.replace(/-/g,"/"));
		$scope.end_date = Date.parse(end_date.replace(/-/g,"/"));
		
		console.log(start_date + " asd "+ end_date);
	});
	
	$scope.queryByPickerDay = function (data){
		if($scope.start_date=='' || $scope.end_date=='') return true; 
		var date ;
		if($scope.dateBy == 'redeem_time'){
			date = new Date(data.redeem_time.replace(/-/g,"/"));
		}
		else{
			date = (data.claim_time != null) ? new Date(data.claim_time.replace(/-/g, "/")) : "";
		}
		
		if (date >= $scope.start_date && date <= $scope.end_date) {
			return true;
		}
		return false;
		
	}
	 
	$scope.detail_store = function(idx){
		var str="";
		if($scope.search_list[idx].claim_time == null)
		{
		
		}
		else{
		str+= "Store ID : "+$scope.search_list[idx].outlet_id+"<br/>"; 
		str+= "Store Name : "+$scope.search_list[idx].outlet_name+"<br/>";
		str+= "Store Address : "+$scope.search_list[idx].outlet_address+"<br/>";
		return str;
		}
	}
	
	$scope.createPanelPromo = function(data){
		$("#detail_promo").html(	
		'<div class="form-horizontal">'+
		'<div class="promo_content">'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Promo ID</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.promo_id+'</label>'+
		'</div>'+
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Promo Name</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.promo_name+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Start Promo</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.start_time+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">End Promo</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.end_time+'</label>'+
		'</div>'+
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Promo Image</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+"<img src='http://117.102.247.5/static/public/"+data.promo_image+"'/>"+'</label>'+
		'</div>'+
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Eligible Device</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.product_name+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Promo Manager</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.pm_name+'</label>'+
		'</div>'+
		
		'<div class="form-group">'+
			'<label class="col-sm-3 control-label" for="inputEmail3">Terms & Conditions</label>'+
			'<label class="col-sm-9 control-label" for="inputEmail3">'+data.tnc+'</label>'+
		'</div>'
		);
		
		$('#promo_number tr').html(
		"<td>" +data.total_coupon+"</td>"+
		"<td>" +data.total_redeem+"</td>"+
		"<td> " +(parseInt(data.total_coupon) - parseInt(data.total_redeem))+"</td>"+
		"<td> " +data.total_claim+"</td>"+
		"<td>" +(parseInt(data.total_redeem) - parseInt(data.total_claim)) +"</td>"
		);
	};
	
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
				'<label class="col-sm-5 control-label" for="inputEmail3">Date Of Birth</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_dob+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Gender</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_gender+'</label>'+
			'</div>'+
			
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Marital Status</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_marital+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Marital Kid Count</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_kid+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Religious Feast</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_religion+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Address</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.user_address+'</label>'+
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
}]);
					
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
			if (last_offset >= limit_row)
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

app.directive('tesPopup',function(){
	return {
		link: function($scope, elem, attrs){
			elem.bind("click", function() {
				$("#promo_number tr").html("");
				$("#detail_promo").html("Loading....");
				$scope.search_promo(attrs.promodata, attrs.batchdata, attrs.campaigndata, attrs.providerdata, attrs.channeldata);
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

	$('#redeem').change(function() {
		if($(this).val() == "redeem_time") {
			document.getElementById("time_filter").className = "btn btn-success";
			document.getElementById("start_date_btn").className = "btn btn-success";
			document.getElementById("redeem/claim").className = "form-group has-success";
			document.getElementById("apply_date").className = "btn btn-success pull-right";
		} else {	
			document.getElementById("time_filter").className = "btn btn-danger";
			document.getElementById("start_date_btn").className = "btn btn-danger";
			document.getElementById("redeem/claim").className = "form-group has-error";
			document.getElementById("apply_date").className = "btn btn-danger pull-right";
		}
	});	
	
	$(".bg-log-loading").hide();
	$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	//$( "#datepicker2" ).datepicker({ maxDate: "-3d" });
	
	angular.element($("body")).scope().loadTotal();
	
	$( "#datepicker" ).datepicker();
	$('#datepicker2').removeClass('hasDatepicker');
	$(".user_detail_container").hide();
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
	//$('button.filter_button').removeClass('active');
	//$(this).addClass('active');
	start_date= '';
	end_date = '';
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
				//$('#transaction_table').fixedHeaderTable({ footer: true, altClass: 'odd' });
				// $(window).resize(function(){
						// var $table = $('table#transaction_table'),
						// $bodyCells = $table.find('tbody tr:first').children(),
						// colWidth;
				//	Get the tbody columns width array
					// colWidth = $bodyCells.map(function() {
						// return $(this).width();
					// }).get();
				//	Set the width of thead columns
					// $table.find('thead tr').children().each(function(i, v) {
						// $(v).width(colWidth[i]);
					// }); 
				// });
				// var $demo1 = $('table#transaction_table');
					// $demo1.floatThead({
						// scrollContainer: function($table){
							// return $table.closest('.t-c');
						// }
					// });

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