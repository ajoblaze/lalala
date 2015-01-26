var revenue_pie = $.parseJSON($("#hid_publication_revenue").html());
var contribution_pie = $.parseJSON($("#hid_device_contribution").html());
var download_pie = $.parseJSON($("#hid_download_month").html());
var graph_data;
var revenue_pie_month = new Array();
var contribution_pie_month = new Array();
var download_pie_month = new Array();
var seriesOptions = new Array();
var categories_data = new Array();
var values_rev = new Array();
var revenue_pie_chart;
var download_pie_chart;
var contribution_pie_chart;
var min_month, max_month, min_year, max_year, interval, global_time = "";
var graph_chart;
var controller = "dslimedashboard";

function loadData(obj, arr)
{
	for(var i=0;i<obj.length;i++){
		arr.push(new Array(JS_decode(obj[i]["name"]), parseInt(obj[i]["total"])));
	}
}

function loadGrData(type)
{
	// Clean Series
	seriesOptions = new Array();

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
							name: res[i].name,
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
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
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
							s += '<br/>' + this.series.name + ':  <strong>' +
							formatNumber(this.y,0)+"</strong>";
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

function loadTable(data)
{
	var html = "";
	var total_last = 0, total_this = 0, total_all = 0;
	for(var i=0;i<data.length;i++) {
		html +="<tr>" +
				"<td style='width:400px'>"+data[i].product+"</td>"+
				"<td style='width:15%'>"+data[i].publisher+"</td>"+
				"<td style='width:21%' align='right'>Rp. "+formatNumber(data[i].last_month, 0)+",-</td>"+
				"<td style='width:21%' align='right'>Rp. "+formatNumber(data[i].this_month, 0)+",-</td>"+
				"<td style='width:21%' align='right'>Rp. "+formatNumber(data[i].all_month, 0)+",-</td>"+
				"</tr>";
		total_last += parseInt(data[i].last_month);
		total_this += parseInt(data[i].this_month);
		total_all += parseInt(data[i].all_month);
	}
	$("#revenueTable").html(html);
	$("#total_last").html(formatNumber(total_last,0));
	$("#total_this").html(formatNumber(total_this,0));
	$("#total_all").html(formatNumber(total_all,0));
}

function loadConTable(data)
{
	var html = "";
	for(var i=0; i<data.length; i++) {
		html += "<tr>" +
					"<td align='center'>"+(i+1)+"</td>" + 
				    "<td>Samsung "+data[i].name+"</td>" + 
					"<td align='right'>"+formatNumber(data[i].total,0)+"</td>"+
				"</tr>";
	}
	$("#top5Con").html(html);
}

function loadStatistics(time_data, publisher)
{
	publisher = (typeof publisher == 'undefined') ? "" : publisher;
	time_data = (time_data == "") ? TIME_MONTH : time_data;
	$.ajax({
		url: base_url+"/"+controller+"/retrpub",
		type: "POST",
		data: "time=" + time_data + "&publisher=" + publisher,
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

function loadOther(publisher)
{
	publisher = (typeof publisher == 'undefined') ? "" : publisher;
	$.ajax({
		url: base_url+"/"+controller+"/retrdata",
		type: "POST",
		data: "publisher=" + publisher,
		beforeSend: function(xhr){
			// $("#loading_gif").show();
		},
		error: function(x, status, error){
			console.log('error'+ error);
		},
		success: function(result){	
			// $("#loading_gif").hide();
			var data = $.parseJSON(result);
			$("#pubCount").html(data.pubCount);
			revenue_pie = data.pubPieRevenue;
			revenue_pie_month = new Array();
			download_pie = data.downloadPie;
			download_pie_month = new Array();
			contribution_pie = data.devContribution;
			contribution_pie_month = new Array();
			loadData(revenue_pie, revenue_pie_month);
			loadData(download_pie, download_pie_month);
			loadData(contribution_pie, contribution_pie_month);
			loadPieChart(revenue_pie_chart, "monthly_publication_revenue", revenue_pie_month);
			loadPieChart(download_pie_chart, "download_content", download_pie_month);
			loadPieChart(contribution_pie_chart, "device_contribution", contribution_pie_month);
			loadTable(data.revenueTable);
			loadConTable(data.top5Con);
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
	loadPieChart(revenue_pie_chart, "monthly_publication_revenue", revenue_pie_month);
	loadPieChart(contribution_pie_chart, "device_contribution", contribution_pie_month);
	loadPieChart(download_pie_chart, "download_content", download_pie_month);
}

$(document).ready(function()
{
	loadData(revenue_pie, revenue_pie_month);
	loadData(contribution_pie, contribution_pie_month);
	loadData(download_pie, download_pie_month);

	$("#cbpublisher").change(function(e)
	{
		loadStatistics(global_time, $(this).val());
		loadOther($(this).val())
	});
	
	$('button.filter_button').click(function() {
		$('button.filter_button').removeClass('active');
		$(this).addClass("active");
		var time_data = $(this).data("time");
		var publisher = $("#cbpublisher").val();
		global_time = time_data;
		$(this).addClass('active');
		loadStatistics(time_data, publisher);
	});
	
	$('#export').click(function() {
		$(".bg-log-loading").show();
		$.ajax({
			url: base_url+"/"+controller+"/exportpubcsv",
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