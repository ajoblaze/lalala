var revenue_pie = $.parseJSON($("#hid_monthly_revenue").html());
var download_pie = $.parseJSON($("#hid_monthly_download").html());
var graph_data;
var revenue_pie_month = new Array();
var download_pie_month = new Array();
var seriesOptions = new Array();
var categories_data = new Array();
var values_rev = new Array();
var revenue_pie_chart;
var download_pie_chart;
var min_month, max_month, min_year, max_year, interval, global_time = "";
var graph_chart;
var controller = "dslimedashboard";

function loadData(obj, arr)
{
	for(var i=0;i<obj.length;i++){
		arr.push(new Array(obj[i]["author"], parseInt(obj[i]["total"])));
	}
}

function loadGrData(type)
{
	min_month = graph_data.min_month;
	max_month = graph_data.max_month;
	min_year = graph_data.min_year;
	max_year = graph_data.max_year;
	min_day = graph_data.min_day;
	max_day = graph_data.max_day;
	
	var res = graph_data.result;
	var val_data = new Array();
	var temp, month, week;
	seriesOptions = new Array();
	for(var i=0;i<res.length;i++)
	{
		temp = res[i].data;
		val_data = new Array();
		for (var j=0;j<temp.length; j++) {
			if (type == TIME_MONTH) {
				val_data.push(new Array(Date.UTC(temp[j].revenue_year, temp[j].revenue_month-1, 1),parseInt(temp[j].total_revenue)));
			} else if (type == TIME_WEEK) {
				month = Math.ceil(temp[j].revenue_week / 4);
				week = ((temp[j].revenue_week - 1) % 4);
				val_data.push(new Array(Date.UTC(temp[j].revenue_year, month-1, (week * 7) + 1),parseInt(temp[j].total_revenue)));
			} else if (type == TIME_DAY) {
				val_data.push(new Array(Date.UTC(temp[j].revenue_year, temp[j].revenue_month-1, temp[j].revenue_day),parseInt(temp[j].total_revenue)));
			}
			
		}
		console.log(val_data);
		seriesOptions[i] = {
							name: res[i].publisher,
							data: val_data,
							pointInterval: 60 * 1000,
							marker : {
									enabled : true,
									radius : 3
								},
							tooltip : {
								valueDecimals : 2
							}
						   };
	}
}

function loadPieChart(obj, id, data_list)
{
	obj = $('#'+id).highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: 1,//null,
            plotShadow: false
        },
		colors: ['#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4','#B5CA92','#1aadce','#280101','#EB7272','#B5D989','#87F600','#721B9F','#AA6C39','#BF1876','#6B8484','#002929','#786A00'],
        title: {
            text: ''
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Total : <strong>{point.y}</strong>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} % ',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Browser share',
            data: data_list
        }]
    });
}

function loadGraphChart(type)
{
	var interval = 24 * 60 * 60 * 1000;
	switch(type) {
		case TIME_MONTH : interval *= 30; break;
		case TIME_WEEK : interval *= 7 ; break;
	}
	
	graph_chart = $('#monthly_graph').highcharts('StockChart',  {
				title: {
					text: '',
					x: -20 //center
				},
				//colors: ['#FFFFFF', '#AF7F24', '#263249', '#5F7F90', '#D9CDB6'],
				xAxis: { type: 'datetime',
					min: Date.UTC( min_year, min_month-1, min_day),
					max: Date.UTC(max_year, max_month-1, max_day),
					tickInterval : interval,
					labels: {
						step: 1,
						style: {
							fontSize: '13px',
							fontFamily: 'Arial,sans-serif'
						}
					},
					dateTimeLabelFormats: { // don't display the dummy year
						month: '%b \'%y',
						year: '%Y'
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
						text: 'Total Revenue'
					},
					plotLines: [{
						value: 0,
						width: 1,
						color: '#808080'
					}],
					floor: 0,
					
				},
				  rangeSelector : {
					selected : 1
				},
				navigator: {
					adaptToUpdatedData: true,
					baseSeries: 1,
					series: {
						type: 'line',
						lineColor: 'black'
					}
				},
				tooltip :{
					shared: true,
					formatter: function() {
						
						var s = '<b>Detail</b>';
						
						$.each(this.points, function () {
							s += '<br/>' + this.series.name + ': ' +
							formatNumber(this.y,0);
						});
						
						return s;
					}
				},
				legend: {
					enabled : true
				},
				series: seriesOptions
			});
}

function loadStatistics(time_data)
{
	$.ajax({
		url: base_url+"/"+controller+"/retrtime",
		type: "POST",
		data: "time=" + time_data,
		beforeSend: function(xhr){
			// $("#loading_gif").show();
		},
		error: function(x, status, error){
			console.log('error'+ error);
		},
		success: function(result){	
			// $("#loading_gif").hide();
			graph_data = $.parseJSON(result);
			loadGrData(time_data);
			loadGraphChart(time_data);
						
		}
	});
}

// ----------------------------------------------------------------------------------------------
// EVENT HANDLER
// ----------------------------------------------------------------------------------------------

function readyHandler()
{
	$(".bg-log-loading").hide();
	loadStatistics(TIME_MONTH);
}

function loadHandler()
{
	loadPieChart(revenue_pie_chart, "monthly_revenue", revenue_pie_month);
	loadPieChart(download_pie_chart, "monthly_download", download_pie_month);
}

$(document).ready(function()
{
	loadData(revenue_pie, revenue_pie_month);
	loadData(download_pie, download_pie_month);
	
	$('button.filter_button').click(function() {
		$('button.filter_button').removeClass('active');
		$(this).addClass("active");
		var time_data = $(this).data("time");
		global_time = time_data;
		$(this).addClass('active');
		loadStatistics(time_data);
	});
	
	$('#export').click(function() {
		$(".bg-log-loading").show();
		$.ajax({
			url: base_url+"/"+controller+"/exportgraphcsv",
			type: "POST",
			data: "time=" + global_time,
			beforeSend: function(xhr){
				// $("#loading_gif").show();
			},
			error: function(x, status, error){
				console.log('error'+ error);
			},
			success: function(result){	
				console.log(result);
				$(".bg-log-loading").hide();
				window.location = base_url + "/" + result;	
			}
		});
	});
});