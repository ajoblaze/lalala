// Slime Javascript
var notif = $("#hid_user_notif").val();
var notif_message = $("#hid_user_notif_message").val();
function readyHandler()
{
	if (notif != "" && typeof notif != "undefined"){ 
		console.log(notif);
		if(notif=='failed'){
			showAlert(ALERT_TYPE_FAILED, notif_message);
		}
		else if(notif=='success'){
			showAlert(ALERT_TYPE_SUCCESS, notif_message);
		} else if (notif != "nothing"){
			showAlert(ALERT_TYPE_FAILED, notif_message);
		}
	}
}

$(function () {
	var data; var max_value; var min_value; var values_data_in; var values_data_un;
	var chart;
	var data_csv=""; var time; var start_date=""; var end_date="";
	
	var total_in; var total_un; var total_active_in; var active_in;
	
	var today = new Date();
	
	today = today.setDate(today.getDate() - 30);
	
	var one_month = new Date(today); var interval = 1; var total_data;
	
	var today = new Date(); var dd = today.getDate(); var mm = today.getMonth()+1;
	
	//Because January is 0..
	
	if($("body").length > 0) {
		
		//initial call
		$.ajax({
			url: base_url+"/managesamsung/pullslime",
			type: "GET",
			success: function(result){
				console.log(result);
				data = $.parseJSON(result);
				getCategoriesData(data);
				tableDraw();
			}
		});
		
		function getCategoriesData(array) {
			id_data = [];
			categories_data = [];
			date_data = [];
			values_data_in = [];
			total_in = 0;
			total_data = 0;
			
			for(x in array) {
				id_data.push(array[x]["id"]);
				categories_data.push(array[x]["datelabel"]);
				date_data.push(array[x]["install_date"]);
				values_data_in.push(parseInt(array[x]["daily_install"]));
				total_in += parseInt(array[x]["daily_install"]);
				total_data +=1; 
			}
			
			max_value = Math.max.apply(Math, values_data_in);
			min_value = Math.min.apply(Math, values_data_in);
			interval = Math.ceil(0.1 * total_data);
		}
		
		function tableDraw() {
			$("table#slime_daily_install tbody").empty();
			for(i = total_data-1; i >= 0; i--) {
				$("table#slime_daily_install tbody").append("<tr><td align='center'>" + categories_data[i] + "<td align='center'>" + values_data_in[i] + "</td>" + "<td align='center'>" + "<a href='"+base_url+"/managesamsung/edit?id=" + id_data[i] + "'><button type='button' class='btn btn-primary'><span class='glyphicon glyphicon-pencil'></span></button></a>" + "&nbsp;&nbsp;" + "<button type='button' class='btn btn-danger btn-delete btdeleteslime' data-id='"+id_data[i]+"' data-date='"+date_data[i]+"' ><span class='glyphicon glyphicon-trash'></span></button>" + "</td></tr>");
			}
			
			$(".btdeleteslime").click(function(e)
			{
				var tgl = $(this).data("date");
				var id = $(this).data("id");
				var conf = confirm("Apa anda yakin ingin menghapus data install slime pada tanggal " + tgl + " ?");
				if (conf == true) {
					window.location = base_url+"/managesamsung/delete?id=" + id + "&value=slime";
				}
			});
	
		}
	}
});
