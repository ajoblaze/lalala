var hilang =false;
var pernah=false;
var favouriteAll= $("#favourite_all").html();
var cat = $("#hid_category_id").val();
var offset = 0, last_offset = "-", limit_row=100;

var app=angular.module("appFavourite",['ngSanitize']);
var search = $("#input_search").val();
var title = $("#title").val();
var publisher = $("#publisher").val();

app.controller("favouriteCtrl",function($scope,$http,$sce,$filter){
	$scope.favouriteList= $.parseJSON(favouriteAll);
    $scope.detail = new Array();
	$scope.query={};
	$scope.queryBy="$";
	$scope.totalData = 0;
	$scope.loading = false;
    
    $scope.searchDetail= function($event,data)
    {
		$(".bg-log-loading").show();
        //console.log(data.title_name);
        var responseDetail= $http({
                        method: "POST",
                        url : base_url+"/favourite/detailfavourite",
                        data : $.param({"title_name" : data.title_name}),
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                      });
        
        responseDetail.success(function (data,status,header,config)
        {
			$(".bg-log-loading").hide();
            $scope.detail=data;
			$("#magazineContent").modal("toggle");
        });
    }; 
	
	$scope.trustHtml = function(x)
	{
		return $sce.trustAsHtml(x);
	}
	
	$scope.loadMore = function(offset)
	{		
		$(".bg-loading").show();
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/favourite/loadmore",
								data: $.param({ "offset" : offset, "search" : search, "title" : title,  "publisher" : publisher, "cat" : cat  }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
							
		responsePromise.success(function(data, status, headers, config) {
			if ($scope.favouriteList.length <= 0) {
				$scope.favouriteList = data;
			} else {
				$scope.favouriteList.push.apply($scope.favouriteList,data);		
			}
			
			$scope.loading = (data.length > 0) ? false : true;
			offset += data.length;
			last_offset = data.length;
			$(".bg-loading").hide();
			// reduce width of last column by width of scrollbar
			var $elem = $("#transaction_table");
			var scrollBarWidth = $elem.find('thead').width() - $elem.find('tbody')[0].clientWidth;
			if (scrollBarWidth > 0) {
				// for some reason trimming the width by 2px lines everything up better
				scrollBarWidth -= 2;
				$elem.find('tbody tr:first td:last-child').each(function (i, elem) {
					$(elem).width($(elem).width() - scrollBarWidth);
				});
			}
		});

		responsePromise.error(function(data,status, headers, config) {
			console.log("AJAX Error.");
		});
	};
	
	$scope.loadTotal = function()
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/favourite/gettotal",
								data: $.param({"search" : search, "title" : title,  "publisher" : publisher, "cat" : cat}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
								});
		responsePromise.success(function(data, status, headers, config) {
			$scope.totalData = parseInt(data);
		});
		
		
		responsePromise.error(function(data, status, headers, config) {
			console.log("AJAX Error");
		});
	}
	
	$scope.exportCurrent = function($event)
	{
		var data = $filter('filter')($scope.search_list, $scope.query);
		
		var json = JSON.stringify(data);
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/favourite/exportcsv",
								data: $.param({"export" : 1, "json" : json}),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("exportCurrent Error. "  + data);
		});
	};
	
	$scope.exportAll = function($event)
	{
		var responsePromise = $http({
								method: 'POST',
								url: base_url+"/favourite/exportcsv",
								data: $.param({ "export" : 0, "search" : search, "title" : title,  "publisher" : publisher, "cat" : cat }),
								headers: {'Content-Type': 'application/x-www-form-urlencoded'}
							});
		$(".bg-log-loading").show();					
		responsePromise.success(function(data, status, headers, config)
		{
			$(".bg-log-loading").hide();
			console.log(data);
			window.location = base_url + "/" + data;
		});
		
		responsePromise.error(function(data, status, headers, config)
		{
			console.log("AJAX Error.");
		});
	};
});

app.filter("asDate", function () {
    return function (input) {
        return (input != "") ? new Date(input.replace(/-/g, "/")) : input;
    }
});

app.directive("scroll",[function()
{
	return {
		link : function($scope, element, attrs)
		{
			// ng-repeat delays the actual width of the element.
			// this listens for the change and updates the scroll bar
			function scrollListener() {
				var maxScroll = $("table#transaction_table tbody")[0].scrollHeight - $("table#transaction_table tbody").outerHeight() - 100;
				if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0)
				{
					offset = $("table#transaction_table tbody tr").length - 1;
					last_offset = (last_offset == "-") ? offset : last_offset;
					
					if ($scope.loading == false && last_offset >= limit_row)
					{
						$scope.loadMore(offset);
						$scope.loading = true;
					}
				}
			}

			// so that when you go to a new link it stops listening
			element.on('remove', function() {
			clearInterval(listener);
			});

			var listener = setInterval(function() {
			scrollListener();
			}, 650);
		}
	}
}]);

$(document).ready(function(){
	$("#advanced_search_container").hide();
	$(".bg-log-loading").hide();
	angular.element($("body")).scope().loadTotal();
	$("#advanced_search_button").click(function(){
	if(pernah && !hilang){
	$("#advanced_search_container").slideUp();
			$(".adv-search").removeClass("up");
			$(".adv-search").addClass("down");
			hilang=true;
		}
		else{
			$("#advanced_search_container").slideDown();
			$(".adv-search").removeClass("down");
			$(".adv-search").addClass("up");
			hilang=false;
		}
		pernah=true;
	});
});
