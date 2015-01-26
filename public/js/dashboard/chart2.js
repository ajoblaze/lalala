
$(".bg-loading-segment").hide();
$(".loading-circle").hide();
var timeout = 15*1000;
var chart;
var filter_time;
var time;
var last_time;
var offset_time;
var backdate = 0;
var weekInterval = 7*24*60*60 * 1000;
var dayInterval =60 * 1000;
var interval = weekInterval;
var seriesOptions = [],
	seriesCounter = 0,
	names = ['claim', 'redeem'],
	// create the chart when all data is loaded
	createChart = function () {

	chart = $('#container').highcharts('StockChart', {
		
   chart : {
		events : {
			load : function () {
				setTimeout(function(){ callData(this.series[0], this.series[1]) }, timeout);
			}
		}
	},
	rangeSelector: {
		buttons: [{
			count: 3,
			type: 'hour',
			text: '3H'
		}, {
			count: 15,
			type: 'minute',
			text: '15M'
		}, {
			type: 'all',
			text: 'All'
		}],
		inputEnabled: false,
		selected: 0
	},
	navigator: {
		adaptToUpdatedData: true,
		baseSeries: 1,
		series: {
			type: 'line',
			lineColor: 'black'
		}
	},
	 xAxis: {
		tickInterval:7*24*60*60 * 1000,
	},
	legend: {
		enabled : true
		
		},
			yAxis: {
				plotLines: [{
					value: 0,
					width: 1,
					color: 'silver'
				}],
				floor: 0,
				minTickInterval: 1
			},

			tooltip: {
				valueDecimals: 0,
				pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.x} -> {point.y}</b><br/>',
				formatter:function(){
							  var s = Highcharts.dateFormat("%B %e, %Y", this.x)+'<br/>';
							  var h = Highcharts.dateFormat("%H:%M", this.x);
							$.each(this.points, function () {
								s += '<span style="color:'+this.series.color+'">'+this.series.name+'</span>: <b>'+h+" -> "+this.y+'</b><br/>';
							});
							return s;					
				}
			},

			series: seriesOptions
		});
	};
	
	
function callData(series1, series2){
			var limit_time = offset_time.getHours()+":"+offset_time.getMinutes();
			$.ajax({
				url:base_url+'/dashboard/getclaim',
				type: 'post',
				data: {'date':filter_time, 'start_time':last_time, 'end_time':limit_time, 'promo_id':$("#promo_id").val()},
				error: function(x, error, status){
					console.log(error+status);
				},
				success:function(result){
					var arr = result.split(";");
					filter_time = arr[1];
					last_time = arr[2];
					appendData(series1, series2, $.parseJSON(arr[0]), $.parseJSON(arr[3]));
					setTimeout(function(){ callData(series1, series2) }, timeout)
				}
			}); 		
}

function appendData(series1,series2, data, data2){
		var datas=[];
		datas["claim"]=[];
		datas["redeem"]=[];
		var start_time = getKey(data).split(":");
		var start_date = new Date(filter_time);
		var year = start_date.getFullYear();
		var month = start_date.getMonth();
		var day = start_date.getDate();
		time = Date.UTC(year, month, day, start_time[0], start_time[1]);
		var i=0;
		
		for(x in data){
		 series1.addPoint([time+i*60*1000, parseInt(data[x])], true, true);
		 series2.addPoint([time+i*60*1000, parseInt(data2[x])], true, true);
				i++;
		}
		time = time+i*60*1000;
		var last = last_time.split(":");
		offset_time = new Date(year, month, day, last[0], last[1]);
		offset_time= offset_time.getTime()+ 60*1000*15;
		offset_time = new Date(offset_time);
}

function getKey(data) {
  for (var prop in data)
	if (data.propertyIsEnumerable(prop))
	  return prop;
}
	
function getData(data, data2){
	var datas=[];
	datas["claim"]=[];
	datas["redeem"]=[];
	var start_time = getKey(data).split(":");
	var start_date = new Date(filter_time.replace("-","/"));
	var year = start_date.getFullYear();
	var month = start_date.getMonth();
	var day = start_date.getDate();
	time = Date.UTC(year, month, day, start_time[0], start_time[1]);
	var i=0;
	for(x in data){
			datas["redeem"].push([time+i*60*1000, parseInt(data2[x])]);
			datas["claim"].push([time+i*60*1000, parseInt(data[x])]);
			i++;
	}
	time = time+i*60*1000;
	var last = last_time.split(":");
	offset_time = new Date(year, month, day, last[0], last[1]);
	offset_time= offset_time.getTime()+ 60*1000*15;
	offset_time = new Date(offset_time);
	

	$.each(names, function (i, name) {
		seriesOptions[i] = {
				name: name,
				data: datas[name],
				pointInterval: 60 * 1000,
				marker : {
					enabled : true,
					radius : 3
				},
			tooltip : {
				valueDecimals : 2
			}
		};
	});
	createChart();
}
	/*get group data------------------------*/
function getGroupData(data, data2){
	var datas=[];
	datas["claim"]=[];
	datas["redeem"]=[];
	
	for(x in data){
			datas["redeem"].push([(new Date(data2[x]['time'].substr(0, 10))).getTime(), parseInt(data2[x]['total'])]);
			datas["claim"].push([(new Date(data2[x]['time'].substr(0, 10))).getTime(), parseInt(data[x]['total'])]);
			console.log((new Date(data2[x]['time'].substr(0, 10))).getTime());
	}
	$.each(names, function (i, name) {
		seriesOptions[i] = {
				name: name,
				data: datas[name],
				marker : {
					enabled : true,
					radius : 3
				},
			tooltip : {
				valueDecimals : 2
			}
		};
	});
	createChart();
}

function ajaxCall(){
	var temp_date = new Date();
	var temp_end = temp_date.getHours()+":"+temp_date.getMinutes();
	var current_date = temp_date.getFullYear()+"-"+(temp_date.getMonth()+1)+"-"+(temp_date.getDate()-backdate);
	$(".bg-loading-segment").show();
	$(".loading-circle").show();
	$.ajax({
		url:base_url+'/dashboard/getclaim',
		type: 'post',
		data: {'date':current_date, 'start_time':'10:00', 'end_time':temp_end, 'promo_id':$("#promo").val()},
		error: function(x, error, status){
			console.log(error+status);
		},
		success:function(result){
		// alert(result);
			if(result!='0'){
				var arr = result.split(";");
				filter_time = arr[1];
				last_time = arr[2];
				getData($.parseJSON(arr[0]), $.parseJSON(arr[3]));
				$(".bg-loading-segment").hide();
				$(".loading-circle").hide();
			}
			else{
				console.log('no data');
			}
		}
	});
}
	
function groupCall(type){
		$(".bg-loading-segment").show();
		$(".loading-circle").show();
		$.ajax({
		url:base_url+'/dashboard/getgroupclaim',
		type: 'post',
		data: {'type':type},
		error: function(x, error, status){
			console.log(error+status);
		},
		success:function(result){
			var arr = result.split(";");
			getGroupData($.parseJSON(arr[0]), $.parseJSON(arr[1]));
			$(".bg-loading-segment").hide();
			$(".loading-circle").hide();
		}
	});		
}

//--------------------------------------------------------------------------------------------------------
// Override Event
// -------------------------------------------------------------------------------------------------------

function loadHandler()
{
	/*Initial Call*/
	ajaxCall();
}

function readyHandler()
{
}

//--------------------------------------------------------------------------------------------------------
// Custom Event
// -------------------------------------------------------------------------------------------------------

$(document).ready(function(e){
	$("#daily").addClass("active");
	$(".chart_btn").click(function(){
		ajaxCall();
		$(".filter").removeAttr('disabled');
		$(".filter-chart").removeClass('active');
		$("#daily").addClass("active");
		interval= dayInterval;
	});
	$(".filter-chart").click(function(){
		var type = $(this).data("val");
		$("#daily").removeClass("active");
		$(".filter-chart").removeClass('active');
		$(this).addClass("active");
		$(".filter").attr('disabled','disabled');
		$("#promo").val("");
		groupCall(type);
	});
});