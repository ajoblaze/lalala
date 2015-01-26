// Javascript

$(function () {
	var data; var max_value; var min_value; var values_data_in; var values_data_un;
	
	var data_csv = ""; var time = ""; var start_date = ""; var end_date = "";
	
	var total_in; var total_un; var total_active_in; var active_in;
	
	var today = new Date();
	
	today = today.setDate(today.getDate() - 30);
	
	var one_month = new Date(today); var interval = 1; var total_data;
	
	//if user in slime page
	
	$( "#datepicker1" ).datepicker({ maxDate: "-3d"});
	
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	
	$( "#datepicker2" ).datepicker({ maxDate: "-3d" });
	$( "#datepicker" ).datepicker();
	var today = new Date(); var dd = today.getDate(); var mm = today.getMonth()+1;
	
	//Because January is 0..
	
	var yyyy = today.getFullYear();
	if(dd < 10) {
		dd='0'+dd;
	} 
	
	if(mm < 10) {
		mm='0'+mm
	} 
	
	var today = dd+'/'+mm+'/'+yyyy; 
	$("#start_date_btn").click(function() {
		$('#date_dropdown').slideToggle();
	});
    
	$("#export").click(function(){
		$.ajax({
					url: base_url+"/salaam/installscsv",
					type: "POST",
					data: "data=" + data_csv + "&start=" + start_date + "&end=" + end_date + "&time_input=" + time + "&export_t=" + 1,
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
				//location.href = base_url+"/sgift/downloadcsv?start="+start_date+"&end="+end_date+"&time_input="+time;
	});
	
	$("#apply_date").click(function() {
		
		$('button.filter_button').removeClass('active');
		$('#date_dropdown').slideUp();
		
		end_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker2").datepicker("getDate"));
		start_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker1").datepicker("getDate"));
				
		$.ajax({
			url: base_url+"/salaam/pullunin",
			type: "POST",
			data: {start: start_date, end: end_date},
			beforeSend: function(xhr) {
				$("#loading_gif").show();
			},
			error: function(x, status, error) {
				console.log('error'+ error);
			},
			success: function(result) {
							data = $.parseJSON(result);
							getCategoriesData(data);
							chart.redraw();
							tableDraw();
			}
		});
		
	});
	
	if($("body").length > 0) {
		
		//initial call
		$.ajax({
			url: base_url+"/salaam/pullunin",
			type: "GET",
			success: function(result){
				console.log(result);
				data = $.parseJSON(result);
				getCategoriesData(data);
				tableDraw();
			}
		});
		
		function getCategoriesData(array) {
			categories_data = [];
			values_data_in = [];
			values_data_un = [];
			active_in = [];
			total_in = 0;
			total_un = 0;
			total_active_in = 0;
			total_data = 0;
			
			for(x in array) {
				categories_data.push(array[x]["datelabel"]);
				values_data_in.push(parseInt(array[x]["dailydownload"]));
				values_data_un.push(parseInt(array[x]["daily_uninstall"]));
				active_in.push(parseInt(array[x]["dailydownload"]) - parseInt(array[x]["daily_uninstall"]));
				total_in += parseInt(array[x]["dailydownload"]);
				total_un += parseInt(array[x]["daily_uninstall"]);
				total_data +=1; 
			}
			
			total_active_in = total_in - total_un;
			max_value = Math.max.apply(Math, values_data_in);
			min_value = Math.min.apply(Math, values_data_in);
			interval = Math.ceil(0.1 * total_data);
			init();
			
		}
		
		function tableDraw() {
			
			$("table#slime_daily_install tbody").empty();
			$("table#slime_install tbody tr").html("<td align='center'><h1><b>" + formatNumber(total_in) + "</b></h1></td>" + "<td align='center'><h1><b>" + formatNumber(total_un) + "</b></h1></td>" + "<td align='center'><h1><b>" + formatNumber(total_active_in) + "</b></h1></td>");
			for(i = total_data-1; i >= 0; i--) {
				$("table#slime_daily_install tbody").append("<tr><td align='center'>" + categories_data[i] + "<td align='center'>" + formatNumber(values_data_in[i]) + "</td>" + "<td align='center'>" + formatNumber(values_data_un[i]) + "</td>" + "<td align='center'>" + formatNumber(active_in[i]) + "</td></tr>");
			}
		}
		
			
		var init = function() {
			var chart = $('#container-unin').highcharts( {
				
				title: {
					text: 'Daily Download and Uninstall',
					x: -20 //center
				},
				//colors: ['#FFFFFF', '#AF7F24', '#263249', '#5F7F90', '#D9CDB6'],
				xAxis: {
					type: 'datetime',
					categories: categories_data,
					tickInterval: interval,
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
						text: 'Total Download and Uninstall'
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
					formatter: function() {
						
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
					name: 'Install',
					data: values_data_in,
					color: '#426bca'
				}, {
					name: 'Uninstall',
					data: values_data_un,
					color: '#303030'
				}]
			});
		}
			
		$('button.filter_button').click(function() {
			$('button.filter_button').removeClass('active');
				
				$(this).addClass('active');
				time = $(this).val();
				start_date = "";
				end_date = "";
				$.ajax({
					url: base_url+"/salaam/pullunin",
					type: "POST",
					data: {time_input: time},
					beforeSend: function(xhr){
						$("#loading_gif").show();
					},
					error: function(x, status, error){
						console.log('error'+ error);
					},
					success: function(result){	
									$("#loading_gif").hide();
									data = $.parseJSON(result);
									getCategoriesData(data);
									tableDraw();
									chart.redraw();								
					}
				});
				
		});
		}
});