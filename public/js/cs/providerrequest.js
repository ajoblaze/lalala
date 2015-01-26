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
var table;
var url= $("#hid_param_url").val();
var start_date= $("#hid_param_start_date").val();
var end_date= $("#hid_param_end_date").val();
var period= $("#hid_param_period").val();
var $header = $("#merchant_table1 > thead").clone();
var $fixedHeader = $("#header-fixed").append($header);
var tableOffset = $("#merchant_table1").offset().top;
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
									url: base_url+"/cssgift/loadmoreproviderrequest",
									data: $.param({"page" : page, "q" : q, "url": url, "start_date": start_date, "end_date": end_date, "period":period}),
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
				console.log("AJAX Error."+data);
				$scope.isLoading = false;
			});	
		}
	};
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/gettotalproviderrequest",
								data: $.param({"q" : q, "url": url, "start_date": start_date, "end_date": end_date, "period":period}),
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
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		
		var json = JSON.stringify($scope.search_list);
		alert(JSON.stringify(data))
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
	
	$scope.search_detail = function(id){
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/getproviderrequestdetail",
								data: $.param({"id" : id }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config)
		{
			$scope.createPanelDetail(data[0]);
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("AJAX Error (bingung nih)." + data);
		});
	}
	$scope.printObject = function(key, obj){
		if(typeof obj === 'object' ){
			for (var key in obj) {
				$scope.printObject(key, obj[key]);
			}
		}
		else{
			table+=key+": "+obj+"<br/>";
		}
	};
	$scope.createPanelDetail = function(data){
	// console.log(data.payload);
			var payload = $.parseJSON(data.payload);
			var response = $.parseJSON(data.response);
			var http_headers = $.parseJSON(data.http_headers);
			table="";
			var key='';
			table+='<span style="font-weight:bold;">PAYLOAD :</span><br/>-----------------<br/>';
			$scope.printObject(key, payload);
			table+='<br/>-----------------<br/><span style="font-weight:bold;">RESPONSE :</span><br/>-----------------<br/>';
			$scope.printObject(key, response);
			table+='<br/>-----------------<br/><span style="font-weight:bold;">HTTP HEADERS :</span><br/>-----------------<br/>';
			$scope.printObject(key, http_headers);
			var str ='<div class="form-horizontal">'+
			'<div class="promo_content">'+
				'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">ID :</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.id+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Creation Time : </label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.creation_time +'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-5 control-label" for="inputEmail3">Last Update Time</label>'+
			'<label class="col-sm-7 control-label" for="inputEmail3">'+data.last_update_time+'</label>'+
			'</div>'+
			'<div class="form-group">'+
				'<label class="col-sm-12 control-label" style="height:300px;overflow-y:scroll;font-weight:100;"> <table class="table table-striped">'+
					table
				+'</table></label>'+
			'</div>';
			var str2 = 'ID : '+data.id+'<br/>'+'Creation Time :'+data.creation_time+'<br/>Last Update Time :'+data.last_update_time+'<br/>-----------------<br/>';
			$('#contenttxt').val(str2+table);
			$("#detail_user").html(str);
	}
	
	$scope.exportAll = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/cssgift/exportcsvproviderrequest",
								data: $.param({"export" : 0, "q" : q, "url" : url}),
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
	$scope.exportTxt = function($event)
	{
		$("txtForm").submit()
		// var responsePromise = $http({
								// method: 'POST',
								// url: base_url+"/cssgift/exporttxtdetail",
								// data: $.param({"data":table}),
								// headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							// });
		// $(".bg-log-loading").show();
		// responsePromise.success(function(data, status, headers, config)
		// {
			// alert(table);
			// $(".bg-log-loading").hide();
			// console.log(data);
			 // document.location = base_url+"/cssgift/exporttxtdetail?";
	
		// });
		
		// responsePromise.error(function(data, status, headers, config)
		// {
			// console.log("AJAX Error.");
		// });
	};
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
		var maxScroll = $("table#merchant_table1 tbody")[0].scrollHeight - $("table#merchant_table1 tbody").outerHeight() - 100;
		if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0)
		{
			offset = $("table#merchant_table1 tbody tr").length;
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

app.directive('popupDetail',function(){ 
	return {
		link: function($scope, elem, attrs){
			elem.bind("click", function() {
				$("#detail_user").html("Loading....");
				$scope.search_detail(attrs.detailid);
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
	$('.chk_all').click(function(){
		$('.chk_item').removeAttr('checked');
	});
	$('.chk_item').click(function(){
		$('.chk_all').removeAttr('checked');
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
				
		function adjustTable(){
					var $table = $('table#merchant_table1'),
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
					var $table = $('table#merchant_table1'),
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