// Slime Javascript

$(function () {
	var data; var max_value; var min_value; var values_data_in; var values_data_un;
	var data_csv = ""; var time = ""; var start_date = ""; var end_date = "";
	var total_in; var total_un; var total_active_in; var active_in;
	var today = new Date();
	today = today.setDate(today.getDate() - 30);
	var one_month = new Date(today); var interval = 1; var total_data;
	var detail = [];
	var os = [];
	var device = [];
	var app_version =[];
	//if user in slime page
	
	$( "#datepicker1" ).datepicker({ maxDate: "-3d"});
	
	//$( "#datepicker2" ).datepicker({ defaultDate: '-7d' });
	
	$( "#datepicker" ).datepicker();
	$('#datepicker2').removeClass('hasDatepicker');
	$("#datepicker2").datepicker({
		maxDate: "-3d" ,
		onSelect: function(value, date) {
		$( "#datepicker1" ).datepicker( "option", "maxDate", value );
	}
	});
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
		$('.date_dropdown').slideToggle();
	});
    
	$("#export").click(function(){
		$.ajax({
					url: base_url+"/slime/errorcsv",
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
		$('.date_dropdown').slideUp();
		
		end_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker2").datepicker("getDate"));
		start_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker1").datepicker("getDate"));
				
		$.ajax({
			url: base_url+"/slime/errorreportin",
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
			url: base_url+"/slime/errorreportin",
			type: "GET",
			beforeSend: function(x){
				$("#loading_gif").show();
			},
			error: function(xhr, error, status){
				console.log(status);
			},
			success: function(result){
				console.log (result);
				$(".progress").hide();
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
			detail = [];
			os = [];
			device = [];
			app_version =[];
			for(x in array) {
				categories_data.push(array[x]["datelabel"]);
				values_data_in.push(parseInt(array[x]["daily_crashes"]));
				values_data_un.push(parseInt(array[x]["daily_anrs"]));
				detail.push(array[x]["total_detail"]);
				os.push(array[x]["total_os"]);
				device.push(array[x]["total_device"]);
				app_version.push(array[x]["total_appversion"]);
				active_in.push(parseInt(array[x]["daily_crashes"]) - parseInt(array[x]["daily_anrs"]));
				total_in += parseInt(array[x]["daily_crashes"]);
				total_un += parseInt(array[x]["daily_anrs"]);
				total_data +=1; 
			}
			console.log(categories_data.length);
			
			total_active_in = total_in + total_un;
			max_value = Math.max.apply(Math, values_data_in);
			min_value = Math.min.apply(Math, values_data_in);
			interval = Math.ceil(0.1 * total_data);
			init();
			
		}
		
		function tableDraw() {
				//console.log(total_data);
			$("table#slime_daily_install tbody").empty();
			$("table#slime_install tbody tr").html("<td align='center' data-toggle='tooltip' title='The number of application is not responding and the number of error occurance'><h1><b>" + formatNumber(total_active_in) + "</b></h1></td>" + "<td align='center' data-toggle='tooltip' title='The number of application is not responding'><h1><b>" + formatNumber(total_un) + "</b></h1></td>" + "<td align='center' data-toggle='tooltip' title='The number of error occurance'><h1><b>" + formatNumber(total_in) + "</b></h1></td>");
			for(i = total_data-1; i >= 0; i--) {
				
				$("table#slime_daily_install tbody").append(
				"<tr><td align='center'>" + categories_data[i] + "</td>"+ "<td align='center'>" + values_data_in[i] + "</td><td align='center'>" + values_data_un[i] +"</td>" + "<td align='center'>" + 
				
				"<a><button type='button' data-flag='1' data-tgl='"+categories_data[i]+"' class='btos btn btn-danger' ><span></span>&nbsp; <span class='action-text'>"+os[i]+", See OS...</span>&nbsp;</button></a>" + "</td>" + "<td align='center'>" + 
				"<a><button type='button' data-flag='2' data-tgl='"+categories_data[i]+"' class='btdevice btn btn-warning'><span></span>&nbsp; <span class='action-text'>"+device[i]+", See Device...</span>&nbsp; </button></a>" + "</td>" + "<td align='center'>" + 
				"<a><button type='button' data-flag='3' data-tgl='"+categories_data[i]+"' class='btappversion btn btn-primary'><span></span>&nbsp; <span class='action-text'>"+app_version[i]+", See Version...</span>&nbsp; </button></a>" + "</td>"+ "<td align='center'>" + 
				"<a><button type='button' data-flag='4' data-tgl='"+categories_data[i]+"' class='btdetail btn btn-info'><span></span>&nbsp; <span class='action-text'>"+detail[i]+", See Details...</span>&nbsp; </button></a>" + "</td></tr>");
			}
			
			$(".btos").click(function(e)
			{
				var flag = $(this).data("flag");
				var tgl = $(this).data("tgl");
				$.ajax({
					url: base_url+"/slime/errorpopupin",
					type: "POST",
					dataType: "html",
					data:"flag="+flag+"&tgl="+tgl,
					success: function(result){
						console.log (result);
						data1 = $.parseJSON(result);
						popup_error('OS');
					}
				}); //tutup ajax
			}); //tutup btos
			
		
			
			function popup_error(field) {
			 $('#popup_error').html('')
			 $('#error_panel .panel-title').html("Various "+field.replace('_',' ')+" in a day")
			 $('#error_column').html(field)
				$(".cover").show();
				$('.error_popup_container').animate({
						opacity: 1,
						height: "show"
						}, 'fast', function() {
							//this.hide();
						}); 
				for( x in data1)
				{
					$('#popup_error').append(
					"<tr><td width='120px'>" +data1[x]['datelabel']+"</td>"+
					"<td><div style='width:500px;word-break:break-all;'>" +data1[x][field]+"</div></td>"+
					"<td> " +((data1[x]['daily_crashes']==null)?'0':data1[x]['daily_crashes'])+"</td>"+
					"<td> " +((data1[x]['daily_anrs']==null)?'0':data1[x]['daily_anrs'])+"</td></tr>"
					);
				}
			}
			
			$('#close_error').click(function(){
				$('.error_popup_container').animate({
				opacity: 1,
				height: "hide"
				}, 'fast', function() {
					//this.hide();
				});$(".cover").hide();
			}
			);
			
			$(document).mouseup(function (e)
			{
				var container = $(".error_popup_container");
				if (!container.is(e.target) // if the target of the click isn't the container...
				&& container.has(e.target).length === 0) // ... nor a descendant of the container
				{
					container.animate({
						opacity: 1,
						height: "hide"
						}, 'fast', function() {
						//this.hide();
					});$(".cover").hide();
				}
			});
			
			
			$(".btdevice").click(function(e)
			{
				var flag = $(this).data("flag");
				var tgl = $(this).data("tgl");
				
				$.ajax({
					url: base_url+"/slime/errorpopupin",
					type: "POST",
					dataType: "html",
					data:"flag="+flag+"&tgl="+tgl,
					success: function(result){
						console.log (result);
						data1 = $.parseJSON(result);
						popup_error('Device');
					}
					
					
				}); //tutup ajax
			}); //tutup bt
			
			
			
			$(".btappversion").click(function(e)
			{
				var flag = $(this).data("flag");
				var tgl = $(this).data("tgl");
				
				$.ajax({
					url: base_url+"/slime/errorpopupin",
					type: "POST",
					dataType: "html",
					data:"flag="+flag+"&tgl="+tgl,
					success: function(result){
						data1 = $.parseJSON(result);
						popup_error('App Version');
					}
					
					
				}); //tutup ajax
			}); //tutup bt
			
			$(".btdetail").click(function(e)
			{
				var flag = $(this).data("flag");
				var tgl = $(this).data("tgl");
				
				$.ajax({
					url: base_url+"/slime/errorpopupin",
					type: "POST",
					data:"flag="+flag+"&tgl="+tgl,
					success: function(result){
						data1 = $.parseJSON(result);
						popup_error('Detail');
					}
					
					
				}); //tutup ajax
			}); //tutup bt
			
			
			
		}
		
			
		var init = function() {
			var chart = $('#container-unin').highcharts( {
				
				title: {
					text: 'Daily Crash and Anrs Report',
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
						text: 'Total Crash and anrs'
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
					name: 'Crash',
					data: values_data_in,
					color: '#426bca'
				}, {
					name: 'anrs',
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
					url: base_url+"/slime/errorreportin",
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