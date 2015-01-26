// Javascript
var by = new Array();

function initTableData(col)
{
	$.ajax({
		url : base_url + "/sfit/sort?sorts="+col+"&by="+by[col],
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
				html += "<td class='center'><ul class='star-container'>";
				var star = parseInt(json[i].star_rating.replace("\0",""));
				for( var x = 0 ; x < star; x++){
					html += "<li><span class='glyphicon glyphicon-star'></span></li>";
				}
				
				/*for( var x = 0 ; x < 5 - star; x++){
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
	// initTableData();
	initSort();
	initTableData("");
}

function loadHandler()
{
}

// Custom Event
$(document).ready(function(e)
{
	$(".sortable").click(function(e)
	{
		var col = $(this).attr("data-col");

		initTableData(col);
	});
});

$(function () {

	var data_csv; var time; var start_date; var end_date;
	$("#export").click(function(){
	$.ajax({
				url: base_url+"/sfit/reviewcsv",
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

});