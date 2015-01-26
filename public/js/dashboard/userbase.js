$(document).ready(function(){
var chart;
var premium_data = [];
var total_data = [];
var mid_data = [];
var entry_data = [];
var tab_data = [];
	var categories_data = [];
    var seriesOptions = [];
	var seriesCounter = 0;
	var names = ['total', 'premium', 'mid', 'entry', 'tab'];
	var active_monthly = $.parseJSON($('#activeMonthly').html());
	var active_weekly = $.parseJSON($('#activeWeekly').html());
	var active_daily = $.parseJSON($('#activeDaily').html());
	var get_weekly;
	var get_daily;
	var get_active;
	var total_promo_monthly = [];
	var total_voucher_monthly = [];
	var total_redeem_monthly = [];
	var total_claim_monthly = [];	
	var total_promo_weekly = [];
	var total_voucher_weekly = [];
	var total_redeem_weekly = [];
	var total_claim_weekly = [];	
	var total_promo_daily = [];
	var total_voucher_daily = [];
	var total_redeem_daily = [];
	var total_claim_daily = [];
	var json;
	var listRow = $("#userbase tbody tr");

	var addData = function(data){
		$.each($("#userbase tbody tr"), function (i, name) {
	
			$.each(data, function (y, name) {
				json = $.parseJSON(data[y]);
				// alert(y);
				if (i != 6) {
					if(i%7==0){
						$("#userbase tbody tr:nth-child("+(i+1)+") td:nth-child("+(6+y)+")").html(formatNumber(json[i]));
					}
					else
						$("#userbase tbody tr:nth-child("+(i+1)+") td:nth-child("+(5+y)+")").html(formatNumber(json[i]));	
							
					$("#daily tbody tr:nth-child("+(i+1)+") td:nth-child(2)").html(json[i+5]);
				}
			});	
		});
	}
	var segment=['Total','Premium','New Entry','Tablet'];
	$('.bg-loading-segment').show();
	
	get_active = function(){
		$.ajax({
				url: base_url+'/dashboard/activeuser',
				method: 'get',
				beforeSend: function(x){

				},
				success: function(result){
					var data=result.split(";");
					addData(data);
				}
			});
		};
		get_active();
		/*get monthly*/
		$.ajax({
			url: base_url+'/dashboard/promosegment?time=monthly',
			method: 'get',
			beforeSend: function(x){
				$('#monthly_table .bg-loading-segment').show();
			},
			success: function(result){
				var data=result.split(";");
				total_promo_monthly = $.parseJSON(data[0]);
				total_voucher_monthly = $.parseJSON(data[1]);
				total_redeem_monthly = $.parseJSON(data[2]);
				total_claim_monthly = $.parseJSON(data[3]);
				makeTable_monthly(data);
				get_weekly();
			}
	});
	
	function makeTable_monthly(data){
		var str='';
		for( var x in segment){
			if(x == 0) {
				str+='<tr>'+'<td width="350px">'+
					'<a href="#" onclick="popupsegment(\''+segment[x]+'\')" data-toggle="modal" data-target="#popup_segment_container" class="user_button">'+
					segment[x]+
					'</a>'+
				'</td>';
			}
			else {
				str+='<tr>'+'<td width="350px">'+
					'<a href="#" onclick="popupsegment(\''+segment[x]+'\')" data-toggle="modal" data-target="#popup_segment_container" class="user_button">'+
					segment[x]+
					'</a>'+
				'</td>';
			}
			if(x==0) {
				str+='<td rowspan="4" width="200px" style="vertical-align:middle;border: thin solid #DDD">'+formatNumber(total_promo_monthly[0])+'</td>'+
					 '<td rowspan="4" width="200px" style="vertical-align:middle;border: thin solid #DDD">'+formatNumber(total_voucher_monthly[0])+'</td>';
			}
			str+='<td>'+formatNumber(active_monthly[x])+'</td>'+
				 '<td>'+formatNumber(total_redeem_monthly[x])+'</td>'+
				 '<td>'+formatNumber(total_claim_monthly[x])+'</td>'+
			+'</tr>';
		}
		
		$("#monthly_table tbody").html(str);
		$('#monthly_table .bg-loading-segment').hide();
		$('#monthly_table .loading-circle').hide();
	}
	
	function getSegmentData(array) {
		model_name = [];
		device_name = [];
		total_data = 0;
		
		for(x in array) {
			model_name.push(array[x]["device_model"]);
			device_name.push(array[x]["product_name"]);
			total_data++;
		}
	}
	
	function tableDraw() {
		a = 1;
		$("table.devices_data tbody").empty();
		for(i = 0; i < total_data; i++) {
			$("table.devices_data tbody").append("<tr><td>" + a + "<td>" + model_name[i] + "</td>" + "<td>" + device_name[i] + "</td></tr>");
			a++;
		}
	}
	
	/*get weekly*/
	get_weekly = function(){
		$.ajax({
			url: base_url+'/dashboard/promosegment?time=weekly',
			method: 'get',
			beforeSend: function(x){
				$('#weekly_table .bg-loading-segment').show();
			},
			success: function(result){
				var data=result.split(";");
				total_promo_weekly = $.parseJSON(data[0]);
				total_voucher_weekly = $.parseJSON(data[1]);
				total_redeem_weekly = $.parseJSON(data[2]);
				total_claim_weekly = $.parseJSON(data[3]);
				
				makeTable_weekly(data);
				get_daily();
			}
		});
	}
	function makeTable_weekly(data){
	var str='';
	var i = 0;
		for(x in segment){
		if(x == 0) {
			str+='<tr>'+'<td width="350px">'+
				'<a href="#" onclick="popupsegment(\''+segment[x]+'\')" data-toggle="modal" data-target="#popup_segment_container" class="user_button">'+
				segment[x]+
				'</a>'+
			'</td>';
		}
		else {
			str+='<tr>'+'<td width="350px">'+
				'<a href="#" onclick="popupsegment(\''+segment[x]+'\')" data-toggle="modal" data-target="#popup_segment_container" class="user_button">'+
				segment[x]+
				'</a>'+
			'</td>';
		}
		if(i == 0) {
			str+='<td width="200px" style="vertical-align:middle;border: thin solid #DDD" rowspan="4">'+formatNumber(total_promo_weekly[x])+'</td>'+
				 '<td width="200px" style="vertical-align:middle;border: thin solid #DDD" rowspan="4">'+formatNumber(total_voucher_weekly[x])+'</td>';
				 i++;
		}
		str+='<td>'+formatNumber(active_weekly[x])+'</td>'+
			 '<td>'+formatNumber(total_redeem_weekly[x])+'</td>'+
			 '<td>'+formatNumber(total_claim_weekly[x])+'</td>'+
		+'</tr>';
		}
		$("#weekly_table tbody").html(str);
		$('#weekly_table .bg-loading-segment').hide();
		$('#weekly_table .loading-circle').hide();	
	}
	/*get daily*/
	get_daily = function(){
		$.ajax({
			url: base_url+'/dashboard/promosegment?time=daily',
			method: 'get',
			beforeSend: function(x){
				$('#daily_table .bg-loading-segment').show();
			},
			error: function(x, status, error){
				console.log(status+" "+error)
			},
			success: function(result){
				var data=result.split(";");
				total_promo_daily = $.parseJSON(data[0]);
				total_voucher_daily = $.parseJSON(data[1]);
				total_redeem_daily = $.parseJSON(data[2]);
				total_claim_daily = $.parseJSON(data[3]);
				
				makeTable_daily(data);
			}
		});
	};
	function makeTable_daily(data){
	var str='';
	var i = 0;
		for(x in segment){
		if(x == 0) {
			str+='<tr>'+'<td width="350px">'+
				'<a href="#" onclick="popupsegment(\''+segment[x]+'\')" data-toggle="modal" data-target="#popup_segment_container" class="user_button">'+
				segment[x]+
				'</a>'+
			'</td>';
		}
		else {
			str+='<tr>'+'<td width="350px">'+
				'<a href="#" onclick="popupsegment(\''+segment[x]+'\')" data-toggle="modal" data-target="#popup_segment_container" class="user_button">'+
				segment[x]+
				'</a>'+
			'</td>';
		}
		if(i == 0) {
			str+='<td rowspan="4" width="200px" style="vertical-align:middle;border: thin solid #DDD">'+formatNumber(total_promo_daily[x])+'</td>'+
				 '<td rowspan="4" width="200px" style="vertical-align:middle;border: thin solid #DDD">'+formatNumber(total_voucher_daily[x])+'</td>';
				 i++;
		}
		str+='<td>'+formatNumber(active_daily[x])+'</td>'+
			 '<td>'+formatNumber(total_redeem_daily[x])+'</td>'+
			 '<td>'+formatNumber(total_claim_daily[x])+'</td>'+
		+'</tr>';
		}
		$("#daily_table tbody").html(str);
		$('#daily_table .bg-loading-segment').hide();
		$('#daily_table .loading-circle').hide();
	}
	
	//----------------------------------------------------------
        var createChart = function(){
				var chart = $('#container').highcharts({			
					title: {
						text: 'Active User',
						x: -20 //center
					},
					xAxis: {
						type: 'datetime',
						categories: categories_data,
						// tickInterval: interval,
						tickmarkPlacement: "on",
						dateTimeLabelFormats: {
								day: '%e of %b'
						}
					},
					plotOptions: {
						line: {
							dataLabels: {
								enabled: false,
								style: {
									fontSize: '10pt',
									color: '#ff0000 !important'
								}
							}
						}
					},
					yAxis: {
						title: {
							text: 'Total User'
						},
						plotLines: [{
							value: 0,
							width: 1,
							color: '#808080'
						}],
						floor: 0,
						
					},
					tooltip :{
						shared: true,
						formatter: function(){
								  var s = '<b>' + this.x + '</b>';
								$.each(this.points, function () {
									s += '<br/>' + this.series.name + ': ' +
										this.y;
								});
								return s;
						}
					},
					legend: {
						layout: 'vertical',
						align: 'right',
						verticalAlign: 'middle',
						borderWidth: 0
					},
					series: [{
						name: 'Total',
						data: total_data
					},
					{
						name: 'Premium',
						data: premium_data
					},
					{
						name: 'Mid',
						data: mid_data
					},
					{
						name: 'Entry',
						data: entry_data
					},
					{
						name: 'Tab',
						data: tab_data
					},
					
					]
				});
			}
			function getCategoriesData(data){
				categories_data = [];
				total_data = [];
				premium_data = [];
				mid_data = [];
				entry_data = [];
				tab_data = [];
				
				for(x in data){
						categories_data.push(data[x]["datelabel"]);
						total_data.push(parseInt(data[x]["total"]));
						premium_data.push(parseInt(data[x]["premium"]));
						mid_data.push(parseInt(data[x]["mid"]));
						entry_data.push(parseInt(data[x]["entry"]));
						tab_data.push(parseInt(data[x]["tab"]));
				}
				createChart();
			}

		$(".chart_btn").click(function(){
			$("#container").html("");
			$("#chart-bg").show();
			$("#chart-circle").show();
			$("#chart").slideDown();
			var data = $(this).data('val');
			var app = $(this).data('app');
			$.ajax({
				url:base_url+'/dashboard/getdata?time='+data+'&app='+app,
				type: 'get',
				error:function(x, error, status){
					console.log(error, status)
				},
				success:function(result){
					var data = $.parseJSON(result);
					getCategoriesData(data);
					$("#chart-bg").hide();
					$("#chart-circle").hide();
					$("#container").show();
					seriesCounter += 1;
				}
			});
		});
		
	$(document).mouseup(function(e){
		var container = $("#chart");
		if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
		{
			container.slideUp();
		}
	});
	$('#close_chart').click(function(){
		 $("#chart").slideUp();
	});
	$('#chart').draggable();
});


var allDeviceSegment = $.parseJSON($('#allDeviceSegment').html());

function popupsegment(namaSegment){
	var showAll = false;
	var result="";
	switch (namaSegment){
		case 'Total':
			showAll = true;
		break;
		
		case 'Premium':
		break;
		
		case 'Medium':
			namaSegment = 'Mid';
		break;
		
		case 'New Entry':
			namaSegment = 'Entry';
		break;
		
		case 'Tablet':
			namaSegment = 'Tab';
		break;
	}

	result +=
	'<table class="table table-striped">'+
		'<thead>'+
			'<tr>'+
				'<td>No</td>'+
				'<td>Device Model</td>'+
				'<td>Product Name</td>'+
			'</tr>'+
		'</thead>'+
		'<tbody scroll>'
	;
	var number = 1;
	for(var i=0;i<allDeviceSegment.length;i+=1){
		if(showAll==true || namaSegment ==allDeviceSegment[i]['segment']){
			result +=
				'<tr>'+
					'<td>'+number+'</td>'+
					'<td>'+allDeviceSegment[i]['device_model']+'</td>'+
					'<td><capital>'+allDeviceSegment[i]['product_name']+'</capital></td>'+
				'</tr>'
			;
			number+=1;
		}
	}
	
	result +=
	'</tbody>'+
	'</table>'
	;
	
	$('#popup_segment_body').html(result);
}

