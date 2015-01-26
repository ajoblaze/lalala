// Javascript

// Hi Chart

$(function () {
	var data, all_data;
	var data_csv; var time; var start_date; var end_date;
	var max_value;
	var min_value;
	var total_install, total_uninstall, total_active;
	var data_list = [];
	var all_data_list = [];
	var tes = [1,3,2];
	var today = new Date();
	today = today.setDate(today.getDate() - 30);
	var one_month = new Date(today);
	var interval = 1;
	var total_data;
	var res;
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
		$('.date_dropdown').slideToggle();
		//$("#datepicker2").datepicker("show"));
	});
    
	$("#export").click(function(){
		$.ajax({
					url: base_url+"/sfit/carriercsv",
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
				//location.href = base_url+"/sgift/downloadcsv?start="+start_date+"&end="+end_date+"&time_input="+time;
	});
	
	$("#apply_date").click(function(){
		$('button.filter_button').removeClass('active');
		$('.date_dropdown').slideUp();
		end_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker2").datepicker("getDate"));
		start_date = $.datepicker.formatDate( "yy-mm-dd", $("#datepicker1").datepicker("getDate"));
				$.ajax({
							url: base_url+"/sfit/pullcarrier",
							type: "POST",
							data: {start: start_date, end: end_date},
							beforeSend: function(xhr){
								$(".loading").show();
							},
							error: function(x, status, error){
								console.log('error'+ error+ 'status: '+ status);
							},
							success: function(result){
								res = result.split(";");
								
								$(".loading").hide();
								data = $.parseJSON(res[0]);
								getCategoriesData(data);
								tableDraw()
							}
						});
	});

	if($("body").length>0){
		//initial call
			$.ajax({
				url: base_url+"/sfit/pullcarrier",
				type: "GET",
				error: function(x, status, error){
						console.log('error: '+ error);
				},
				success: function(result){	
					res = result.split(";");
					$(".loading").hide();
					data = $.parseJSON(res[0]);
					all_data = $.parseJSON(res[3]);
					getCategoriesData(data);
					getAllData(all_data);
					//console.log(result);
				}
			});
			function getCategoriesData(array){
			total_install = total_other = total_active = 0;
			data_list = [];
				for(x in array){
						data_list.push(new Array(array[x]["carrier_name"], parseInt(array[x]["current_device_installs"])));
					//	console.log(data_list[x]);
						total_install += parseInt(array[x]["current_device_installs"]);
						//console.log(total_install);
				}
				
				total_other = parseInt(res[1])-total_install;	
				total_install += total_other;
				data_list.push(new Array('other', total_other));
				total_active = total_install - total_uninstall;
				interval = Math.ceil(0.1 * total_data);
								//	console.log(data_list[0][0]);
				//tableDraw();
				//init();
								//	console.log(data_list[0][0]);

			}
			//for all data
			function getAllData(array){
			total_data = 0;
			all_data_list = [];
				for(x in array){
			//		console.log(array[x].current_user_installs);
						all_data_list.push(new Array(array[x]["carrier_name"], parseInt(array[x]["current_device_installs"])));
					//	console.log(data_list[x]);
						total_data +=1; 
						//console.log(total_install);
				}
				
								//	console.log(data_list[0][0]);
				tableDraw();
				init();
								//	console.log(data_list[0][0]);

			}
			
			function tableDraw(){
				$("table#slime_daily_install tbody").empty();
				$("table#slime_install tbody tr").html("<td align='center'><h1><b>"+ formatNumber(total_install) +"</b></h1></td>"+"<td align='center'><h1><b>"+ formatNumber(res[2]) +"</b></h1></td>");
				for(var i = 0;i<total_data;i++){
				//	console.log(data_list[i][0]);
					$("table#slime_daily_install tbody").append("<tr><td>"+ all_data_list[i][0]+"</td>"+"<td>"+ all_data_list[i][1] +"</td></tr>" );
				}
			}
			var init = function(){
				var chart = $('#container').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: 1,//null,
            plotShadow: false
        },
        title: {
            text: 'Carrier Data'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
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
			$('button.filter_button').click(function(){
				$('button.filter_button').removeClass('active');
					$(this).addClass('active');
					var time = $(this).val();
					$.ajax({
							url: base_url+"/sfit/pulldevices",
							type: "POST",
							data: {time_input: time},
							beforeSend: function(xhr){
								$(".loading").show();
							},
							error: function(x, status, error){
								console.log('error'+ error+ 'status: '+ status);
							},
							success: function(result){	
									res = result.split(";");
								$(".loading").hide();
								data = $.parseJSON(res[0]);
								getCategoriesData(data);
							}
						});
				});
		}
});