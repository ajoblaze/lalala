// Added by Calvin Windoro 
// on 12 / 09 / 2014
var hilang =false;
var pernah=false;
var data = [];
var trans = [];
var idx = 0;
var page=1;
var flag=0
var offset = 1, offset = 100, last_offset = 0, limit_row = 100;
var start_date='', end_date='';

var q = $("#input_search").val();
var username = $("#username").val();
var email = $("#email").val();
var imei = $("#imei").val();
var device = $("#device").val();
var project = $("#app").val();
var status = $("#status").val();

var $header = $("#transaction_table > thead").clone();
var $fixedHeader = $("#header-fixed").append($header);
var tableOffset = $("#transaction_table").offset().top;

var app = angular.module('customer', []);
app.filter('startFrom', function() {
	return function(input, start) {
	if(input) {
	start = +start; //parse to int
	return input.slice(start);
	}
	return [];
	}
});

app.controller('searchCrtl', function ($scope, $http, $filter) {
	$scope.query = {}
	$scope.queryBy = "$"
	$scope.ids = 0;
	
	$scope.search_list =$.parseJSON($('#customer_search').html());
	
	$scope.history = new Array();
	
	$scope.predicate = '-x';
	$scope.DateRange = "";
	$scope.loading = false;
	$scope.dateBy = 'redeem_time';
	$scope.totalData = 0;
	$scope.hid_email_app = "";
	
	$scope.ff = $('.t-c').scroll(function(){
		if($(this).scrollTop() == $(this)[0].scrollHeight - $(this).height() ){
			offset=offset+1;
			$scope.loading = true;
			$scope.loadMore(offset);
		}
	});
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/customer/gettotal",
								data: $.param({"q" : q, "email" : email, "imei" : imei, "device" : device, "app" : project, "status" : status, "username" : username }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.loadMore = function(offset)
	{		
		console.log("Manggil");
		$(".bg-loading").show();
		var offs = (last_offset > limit_row) ? limit_row : last_offset;
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/customer/loadmore",
								data: $.param({"offset" : offset, "q" : q, "email" : email, "imei" : imei, "device" : device, "app" : project, "status" : status, "username" : username }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.search_list.length <= 0) {
				$scope.search_list = data;
			} else {
				$scope.search_list.push.apply($scope.search_list,data);		
			}
			$scope.loading = (data.length >= 0) ? false : true;
			offset += data.length;
			last_offset = data.length;
			$(".bg-loading").hide();
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error. " + status);
		});	
	};
	
	$scope.filterCustomer = function(app)
	{
		var myArray = new Array();
		var json_users = $.parseJSON($('#customer_users_app').html());
		for(var i=0;i<json_users.length;i++){
			if(json_users[i].projectName != "" && json_users[i].projectlink.toLowerCase().indexOf(app.toLowerCase())>-1
				&& (json_users [i].roleName == "Administrator" || json_users [i].roleName == "Super Administrator"
			)
			){
				myArray.push(json_users [i]['username']);
			}
		}
		return myArray;
	}

	$scope.changeEmail = function($event, x)
	{
		if (x.imei != "") {
			var mode = "email";
			
			// Clear value
			$("#newemail").val("");
			$("#reasonChange").val("");
			
			// Change TO value
			var myArray = $scope.filterCustomer(x.app);
			$("#cbtoChange").empty();
			var $el = $("#cbtoChange");
			$el.empty(); // remove old options
			$.each(myArray, function(key ,value) {
			  $el.append($("<option></option>")
				 .attr("value", value).text(value));
				 
			});
			$("#cbtoChange").val(myArray).trigger("chosen:updated");
			// Change TO value
			
			$("#hid_email").val(x.email);
			$("#hid_email_name").val(x.name);	
			$("#hid_email_app").val(x.app);
			$("#hid_email_deviceid").val(x.imei);
			$("#hid_email_devicetype").val(x.type);
			$("#hid_email_status").val(mode);
			$("#oldemail").val(x.email);
		} else {
			$(".close").click();
			showAlert(ALERT_TYPE_FAILED, "Change email failed due to no imei available.");		
		}
	};
								
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	 };
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		
		var json = JSON.stringify(data);

		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/customer/exportcsv",
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
	
	$scope.exportAll = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/customer/exportcsv",
								data: $.param({"export" : 0, "q" : q, "username" : username, "email" : email , "imei" : imei, "device" : device, "app" : project, "status" : status}),
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
	 
	$scope.doSuspend = function(x, $event)
	{
		var email = x.email;
		var deviceid = x.imei;
		var devicetype = x.type;
		var name = x.name;
		var app = x.app;
		var mode;
		if (x.status == 'active') {
			mode = 'suspend';
		} else  {
			mode = 'activate';
		}
		
		// Change TO value
		var myArray = $scope.filterCustomer(x.app);
		$("#cbto").empty();
		var $el = $("#cbto");
		$el.empty(); // remove old options
		$.each(myArray, function(key ,value) {
		  $el.append($("<option></option>")
			 .attr("value", value).text(value));
		});
		$("#cbto").val(myArray).trigger("chosen:updated");
		// Change TO value
		
		$("#reason_email").val("");
		
		$("#hid_email").val(email);
		$("#hid_email_deviceid").val(deviceid);
		$("#hid_email_devicetype").val(devicetype);
		$("#hid_email_name").val(name);	
		$("#hid_email_app").val(app);
		$scope.hid_email_app = app;
		$("#hid_email_status").val(mode);
		
		$("#subject_email").val("Request to " + mode + " user " + name + " for " + app + "'s app" );
	};
	
	$scope.getHistory = function($event, data)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/customer/gethistory",
								data: $.param({"deviceid" : data.imei, "app" : data.app}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();	
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log(data);
			$scope.history = data;
			$("#detailHistory").modal("toggle");
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
			last_offset = (last_offset == "") ? offset : last_offset;
			if ($scope.loading == false && last_offset >= limit_row)
			{
				$scope.loadMore(offset);
				$scope.loading = true;
				page++;
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

app.directive('tesPopup',function(){
	return {
		link: function($scope, elem, attrs){
			elem.bind("click", function(test) {
				$('.promo_detail_container').animate({
						opacity: 1,
						left: "250",
						height: "show"
						}, 'fast', function() {
							//this.hide();
						});
					$("#detail_promo").html(	
				'<div class="form-horizontal">'+
				'<div class="promo_content">'+
					'<div class="form-group">'+
					'<label class="col-sm-3 control-label" for="inputEmail3">Promo ID</label>'+
				'<label class="col-sm-9 control-label" for="inputEmail3">'+$scope.search_list[attrs.test].promo_id+'</label>'+
				'</div>'+
				'<div class="form-group">'+
					'<label class="col-sm-3 control-label" for="inputEmail3">Promo Name</label>'+
				'<label class="col-sm-9 control-label" for="inputEmail3">'+$scope.search_list[attrs.test].promo_name+'</label>'+
				'</div>'+
				'<div class="form-group">'+
					'<label class="col-sm-3 control-label" for="inputEmail3">Promo Image</label>'+
				'<label class="col-sm-9 control-label" for="inputEmail3">'+$scope.search_list[attrs.test].promo_image+'</label>'+
				'</div>'+
				'<div class="form-group">'+
					'<label class="col-sm-3 control-label" for="inputEmail3">Eligible Device</label>'+
				'<label class="col-sm-9 control-label" for="inputEmail3">'+$scope.search_list[attrs.test].eligible_device+'</label>'+
				'</div>'
				+'<hr>'
				);
				
				
				$('#promo_number tr').html(
				"<td>" +$scope.search_list[attrs.test].total_coupon+"</td>"+
				"<td>" +$scope.search_list[attrs.test].total_redeem+"</td>"+
				"<td> " +$scope.search_list[attrs.test].remaining_redeem+"</td>"+
				"<td> " +$scope.search_list[attrs.test].total_claim+"</td>"+
				"<td>" +$scope.search_list[attrs.test].remaining_claim+"</td>"
				);
			} );
		}
	};
});
$(document).ready(function(){
	$(".bg-log-loading").hide();
	$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	//$( "#datepicker2" ).datepicker({ maxDate: "-3d" });
	$( "#datepicker" ).datepicker();
	$('#datepicker2').removeClass('hasDatepicker');
	$('#close_promo').click(function(){
						$('.promo_detail_container').animate({
						opacity: 1,
						left: "250",
						height: "hide"
						}, 'fast', function() {
							//this.hide();
						});
	});
	
	angular.element($("body")).scope().loadTotal();
	
	$(document).mouseup(function (e)
	{
    var container = $(".promo_detail_container");

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
//$("#send_admin").hide();
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