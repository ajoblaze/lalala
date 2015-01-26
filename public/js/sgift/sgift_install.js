// Javascript

// Hi Chart

$(function () {
	var data;
	var data_csv = "";
	var time = "";
	var start_date = "";
	var end_date = "";
	var max_value;
	var min_value;
	var total_install, total_uninstall, total_active;
	var categories_data = [];
	var values_data = [];
	var uninstall_data =[];
	var active_install = [];
	var tes = [1,3,2];
	var today = new Date();
	today = today.setDate(today.getDate() - 30);
	var one_month = new Date(today);
	var interval = 1;
	var total_data;
	//if user in sgift page
	$( "#datepicker1" ).datepicker({ maxDate: "-3d"});
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	//$( "#datepicker2" ).datepicker({ maxDate: "-3d" });
	$( "#datepicker" ).datepicker();
	$('#datepicker2').removeClass('hasDatepicker');
	$("#datepicker2").datepicker({
		maxDate: "-3d" ,
		onSelect: function(value, date) {
		$( "#datepicker1" ).datepicker( "option", "maxDate", value );
	}
	});
	
	var today = new Date(); var dd = today.getDate(); var mm = today.getMonth()+1;
	//January is 0!
	var yyyy = today.getFullYear();
	if(dd<10){
	dd='0'+dd;
	} 
	if(mm<10){
	mm='0'+mm
	} 
	var today = dd+'/'+mm+'/'+yyyy; 
	$("#start_date_btn").click(function(){
		$('#date_dropdown').slideToggle();
		//$("#datepicker2").datepicker("show"));
	});
	
	$("#export").click(function(){
		$.ajax({
					url: base_url+"/sgift/downloadcsv",
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
						console.log(result);
						$(".loading").hide();
						window.location = base_url + '/'+result;
					}
				});
				//location.href = base_url+"/sgift/downloadcsv?start="+start_date+"&end="+end_date+"&time_input="+time;
	});
    
	$("#apply_date").click(function(){
		$('button.filter_button').removeClass('active');
		$('#date_dropdown').slideUp();
		end_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker2").datepicker("getDate"));
		start_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker1").datepicker("getDate"));
				$.ajax({
							url: base_url+"/sgift/pull",
							type: "POST",
							data: {start: start_date, end: end_date},
							beforeSend: function(xhr){
								$(".loading").show();
							},
							error: function(x, status, error){
								console.log('error'+ error+ 'status: '+ status);
							},
							success: function(result){
										$(".loading").hide();
											data = $.parseJSON(result);
											getCategoriesData(data);
											tableDraw()
											chart.redraw();
							}
						});
	});

	if($("body").length>0){
		//initial call
			$.ajax({
				url: base_url+"/sgift/pull",
				type: "GET",
				error: function(x, status, error){
						console.log('error: '+ error);
				},
				success: function(result){
					$(".loading").hide();
					data = $.parseJSON(result);
					getCategoriesData(data);
					tableDraw()
				}
			});
			function getCategoriesData(array){
			categories_data = [];
			values_data = [];
			uninstall_data = [];
			active_install = [];
			total_install = total_uninstall = total_active = 0;
			total_data = 0;
				for(x in array){
						categories_data.push(array[x]["datelabel"]);
						values_data.push(parseInt(array[x]["dailydownload"]));
						uninstall_data.push(parseInt(array[x]["daily_uninstall"]));
						active_install.push(parseInt(array[x]["dailydownload"]) - parseInt(array[x]["daily_uninstall"]));
						total_data +=1; 
						total_install += parseInt(array[x]["dailydownload"]);
						total_uninstall += parseInt(array[x]["daily_uninstall"]);
				}
				total_active = total_install - total_uninstall;
				max_value = Math.max.apply(Math, values_data);
				min_value = Math.min.apply(Math, values_data);
				interval = Math.ceil(0.1 * total_data);
				init();
			}
			function tableDraw(){
				$("table#slime_daily_install tbody").empty();
				$("table#slime_install tbody tr").html("<td align='center'><h1><b>"+ formatNumber(total_install) +"</h1></b></td>"+"<td align='center'><h1><b>"+ formatNumber(total_uninstall) +"</h1></b></td>"+"<td align='center'><h1><b>"+ formatNumber(total_active) +"</h1></b></td>");
				for(var i = total_data-1;i >= 0;i--){
					$("table#slime_daily_install tbody").append("<tr><td align='center'>"+ categories_data[i]+"</td>"+"<td align='center'>"+ formatNumber(values_data[i]) +"</td>"+"<td align='center'>"+ formatNumber(uninstall_data[i]) +"</td>" + "<td align='center'>"+ formatNumber(active_install[i]) +"</td></tr>" );
				}
			}
			var init = function(){
				var chart = $('#container').highcharts({
					
					title: {
						text: 'Daily Install and Uninstall',
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
							text: 'Total Install'
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
						name: 'Install',
						data: values_data
					},
					{
						name: 'Uninstall',
						data: uninstall_data
					}
					]
				});
			}
			$('button.filter_button').click(function(){
				$('button.filter_button').removeClass('active');
					$(this).addClass('active');
					time = $(this).val();
					start_date = "";
					end_date = "";
					$.ajax({
							url: base_url+"/sgift/pull",
							type: "POST",
							data: {time_input: time},
							beforeSend: function(xhr){
								$(".loading").show();
							},
							error: function(x, status, error){
								console.log('error'+ error+ 'status: '+ status);
							},
							success: function(result){	
											$(".loading").hide();
											data = $.parseJSON(result);
											getCategoriesData(data);
											tableDraw();
											chart.redraw();
							}
						});
				});
		}
});