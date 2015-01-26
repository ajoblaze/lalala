// Javascript
var hilang = false;
var pernah = false;
var feedback = parseInt($("#hid_feedback").val());
var page=1;
var offset = 1, last_offset = 0, limit_row = 100;
var start_date='', end_date='';
var tmp_id = "", tmp_index= 0;
var app = angular.module("reviewApp", []);
var q = $("#input_search").val(), date = $("#date").val(), name = $("#name").val(), email = $("#email").val(), rating = $("#rating").val(), review = $("#review").val();
app.controller("reviewCrtl", function($scope, $http,  $filter){
	$scope.predicate = '-x';
	$scope.query={};
	$scope.queryBy = "$";
	$scope.DateRange = "";
	$scope.loading = true;
	$scope.review_list =$.parseJSON($('#review_result').html());	
	
	$scope.ff = function()
	{$('#transaction_table tbody').scroll(function(){
		if( $(this).scrollTop() == $(this)[0].scrollHeight - $(this).height() ){
			offset = $("table#transaction_table tbody tr").length;
			last_offset = (last_offset == "") ? offset : last_offset;
			if ($scope.loading == true && last_offset >= limit_row) {
				$scope.loadMore(offset);
			}
		}
	});
	}
		$scope.adjustTable = function(){
			var $table = $('table#appreview_table'),
				$bodyCells = $table.find('tbody tr:first').children(),
				colWidth;
			colWidth = $bodyCells.map(function() {
			//	alert($(this).width());
				return $(this).width();
			}).get();
			$('table#appreview_table').find('thead tr').children().each(function(i, v) {
				$(v).width(colWidth[i]);
			}); 
		}
	$scope.sort_by = function(predicate) {
		 $scope.predicate = predicate;
		 $scope.reverse = !$scope.reverse;
	 };
	$scope.loadMore = function(page)
	{		
		$scope.loading=false;
		$(".bg-loading").show();
		console.log("Offset : " + page);
			var responsePromise = $http({
								method: 'POST',
								url: base_url+"/sgift/getreview",
								data:  $.param({'page': page, 'q': q, 'date': date, 'name': name, 'email': email, 'rating': rating, 'review': review}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			console.log(data);
			if ($scope.review_list.length <= 0) {
				$scope.review_list = data;
			} else {
				$scope.review_list.push.apply($scope.review_list,data);		
			}
			$scope.loading = (data.length > limit_row) ? false : true;
			last_offset = data.length;
			$(".bg-loading").hide();
			$scope.loading=true;
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
		});
	};
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.review_list, $scope.query);
		data = $filter('filter')(data, $scope.queryByDay);
		data = $filter('filter')(data, $scope.queryByPickerDay);
		
		var json = JSON.stringify(data);
		
		console.log(json);
		
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/sgift/exportcsv",
								data: $.param({"export" : 1, "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config)
		{
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("exportCurrent Error. "  + data);
		});
	};
	
	$scope.viewReply = function(data, idx, $event){
		$(".bg-log-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/slime/getreply",
								data:  $.param({'replyid': data.reply}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			$(".bg-log-loading").hide();
			$("#replyFeedback").modal("toggle");
			$("#feedback_date").html(data["created_date"]);
			$("#feedback_sender").html(data["username"]);
			$("#feedback_msg").html(data["messages"]);
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error. ");
		});	
	};
	$scope.sendReply = function($event){
		var email = $("#hid_feedback_email").val();
		var name =	$("#hid_feedback_name").val();
		var feedbackid =	$("#hid_feedback_feedbackid").val();
		var projectid =	$("#hid_feedback_projectid").val();
		var message =	$("#message_email").val();
		$("#progressbar").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/sgift/reply",
								data:  $.param({'to_email' : email, 'feedback_id' : feedbackid, 'projectid' : projectid, 'messages' : message}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		responsePromise.success(function(data, status, headers, config) {
			$("#progressbar").hide();
			$(".close").click();
			switch(parseInt(data.status))
			{
				case 1: console.log("Feedback has been sent..");
						showAlert(ALERT_TYPE_SUCCESS, "Feedback has been sent..");
						$scope.review_list[tmp_index].reply = data.reply;
						;break;
					
				default : console.log("Send feedback failed.");
						  $(".btn-feedback[data-id='"+ tmp_id +"']").hide();
						  $(".btn-reply[data-id='"+ tmp_id +"']").show();
						  showAlert(ALERT_TYPE_FAILED, "Send feedback failed.");break;
			}
		});
		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
		});
	}
	
	$scope.doReply = function(x, idx, $event)
	{
		tmp_id = x.id;
		tmp_index = idx;
		var email = x.email;
		var review = x.feedback;
		var name = x.name;
		var projectid = x.projectid;
		var feedbackid = x.id;
		var mode;
		$("#message_email").val("");
		$("#hid_feedback_email").val(email);
		$("#hid_feedback_review").val(review);
		$("#hid_feedback_name").val(name);	
		$("#hid_feedback_projectid").val(projectid);
		$("#hid_feedback_feedbackid").val(feedbackid);
		$("#receiver").val(email);
		$scope.hid_email_app = app;
	};	
	
	$scope.exportAll = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/sgift/exportcsv",
								data: $.param({"export" : 0, 'date': date, 'name': name, 'email': email, 'rating': rating, 'review': review}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config)
		{
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("AJAX Error.");
		});
	};
		$scope.queryByDay = function (data){
		var now = new Date();
		var date ;
		date= new Date(data.review_date.replace(/-/g,"/"));	
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
		date= new Date(data.review_date.replace(/-/g,"/"));
		date= Date.parse(date);
		
		if(date >= start_date && date <= end_date){
			return true;
		}
		return false;
		
	}
	$("#apply_date").click(function(){
		$('button.filter_button').removeClass('active');
		$('.date_dropdown').slideUp();
		end_date = $.datepicker.formatDate( "yy-mm-dd 23:59:59", $("#datepicker2").datepicker("getDate"));
		start_date = $.datepicker.formatDate( "yy-mm-dd 00:00:00", $("#datepicker1").datepicker("getDate"));
		start_date = Date.parse(start_date.replace(/-/g,"/"));
		end_date = Date.parse(end_date.replace(/-/g,"/"));
	});
});


app.filter('num', function() {
		return function(input) {
		  return parseFloat(input, 10);
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
			if ($scope.loading == false && last_offset >= 25)
			{
				$scope.loadMore(page);
				$scope.loading = true;
				page++;
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
	app.directive('makeStar', function(){
	return {
		link: function($scope, elem, attrs){
		var x = attrs.amount;
		if(x>0){
			var str='<ul class="star-container">';
			for(var i = 0 ; i < x; i++){
				str+='<li><span class="glyphicon glyphicon-star"></span></li>';
			}
			elem.html(str+"</ul>");
			}
			else{
				elem.html("No rating");
			}
		}
	};

});
app.filter('asDate', function() {
	return function(input) {
		return (input != null ) ? new Date(input.replace(/-/g, "/")) : "";
	}
});

var by = new Array();


function initTableData(col)
{
	$.ajax({
		url : base_url + "/sgift/sort?sorts="+col+"&by="+by[col],
		dataType : "html",
		data : "GET",
		success : function(result)
		{
			console.log(result);
			var json = $.parseJSON(result);
			var html = "";
			for (var i=0; i < json.length ; i++)
			{
				html += "<tr>";
				html += "<td>" + json[i].review_date.replace(/[T|Z]/g, ' ')+"</td>";
				html += "<td align='center'><ul class='star-container'>";
				var star = parseInt(json[i].star_rating.replace("\0",""));
				for( var x = 0 ; x < star; x++){
					html += "<li><span class='glyphicon glyphicon-star'></span></li>";
				}
				
				/*{for( var x = 0 ; x < 5 - star; x++){
					html += "<li><span class='glyphicon glyphicon-star-empty'></span></li>";							
				}*/					
				html += "</ul></td>"; 
				
				if (json[i].review_title.length > 1)
				{
					html +=	"<td>" + json[i].review_title + "</td>" +
							"<td>" + json[i].review_text + "</td>";
				}
				else
				{
					html +=	"<td> <strong>No review </strong></td>" +
							"<td> <strong>No review</strong> </td>";
				}
					
				html += "</tr>";
			}
			
			by[col] = (by[col] == "desc") ? "asc" : "desc";
			$("#table_data").html(html);
		}
	});
}

function initSort()
{
	var sortable = $(".sortable");
	
	for (var i = 0 ; i <sortable.length ; i++)
	{
		var col = $(sortable[i]).attr("data-col");
		by[col] = "desc";
	}
}

// Override function
function readyHandler()
{
	initSort();
	initTableData("review_date");
	if (feedback == 1) { $("#btapp").click(); }
}
$("#tabs").tab();
function loadHandler()
{
}

// Custom Event
$(document).ready(function(e)
{
	$(".bg-loading").hide();
	$(".bg-log-loading").hide();
	$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	$( "#datepicker2" ).datepicker({ maxDate: "-0d"});
	$(".sortable").click(function(e)
	{
		var col = $(this).attr("data-col");
		initTableData(col);
	});
	
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

$(function () {
		$( "#datepicker1" ).datepicker({ maxDate: "-0d"});
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	//$( "#datepicker2" ).datepicker({ maxDate: "-3d" });
	$( "#datepicker2" ).datepicker();
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

	var data_csv; var time; var start_date; var end_date;
	$("#export").click(function(){
	$.ajax({
				url: base_url+"/sgift/reviewcsv",
				type: "POST",
				data: {data: data_csv, start:start_date, end:end_date, time_input:time, export_t:1},
				beforeSend: function(xhr){
					$(".loading").show();
				},
				error: function(x, status, error){
					console.log('error'+ error+ 'status: '+ status);
				},
				success: function(result){
				//var p = result.split(";");
					//console.log(result);
					$(".loading").hide();
					window.location = base_url + '/'+result;
				}
			});
	});

});