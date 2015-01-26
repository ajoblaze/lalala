var viewAdvSearch=false
var subscriptionOrder=$("#hid_subscription").html();
var page=1;
var flag=0, lock =false;
var offset = 1, last_offset = "-", limit_row = 100;

var search = $("#input_search").val();
var order_id = $("#order_id").val();
var name = $("#name").val();
var email = $("#email").val();
var start_order = $("#start_order").val();
var end_order = $("#end_order").val();
var transaction_id = $("#transaction_id").val();
var payment_type = $("#payment_type").val();
var status = $("#status").val();
var payment=angular.module("payment",[]);
payment.controller("paymentCtrl",function($scope,$filter,$http)
{
    $scope.query={};
    $scope.queryBy="$";
    $scope.subscription=$.parseJSON(subscriptionOrder);
    $scope.detail = new Array();
    $scope.detailData = new Object();
    $scope.totalData = 0;
    $scope.Range = "";
    
    $scope.getDetail = function ($event,data)
    {
        $(".bg-log-loading").show();
        $scope.detailData = data;
        var detail=$http({
                        method : 'POST',
                        url :base_url+"/order/detailtransaction",
                        data : $.param({"order_id" : data.order_id}),
                        headers : {'Content-Type': 'application/x-www-form-urlencoded'}
                    });
        
        
        detail.success(function(data,status, headers, config) {
            $(".bg-log-loading").hide();
            $scope.detail = data;
            $("#detailBox").modal("toggle");
            
        });
        
        detail.error(function(data,status, headers, config) {
            console.log("AJAX error");
            $(".bg-loading").hide();
        });
    };
    
    $scope.loadMore = function(page)
    {       
        lock = true;
        $(".bg-loading").show()
        var responsePromise = $http({
                                method: 'POST',
                                url: base_url+"/order/loadmore",
                                data: $.param({"page" : page, "search" : search, "order_id" : order_id, "name" : name, "email" : email, "start_order" : start_order, "end_order": end_order, "transaction_id":transaction_id, "payment_type" : payment_type, "status" : status}),
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                            });
        responsePromise.success(function(data, status, headers, config) {
            
            if ($scope.subscription.length <= 0) {
                $scope.subscription = data;
            } else {
                $scope.subscription.push.apply($scope.subscription,data);       
            }
            $scope.loading = (data.length > 0) ? false : true;
            last_offset = data.length;
            $(".bg-loading").hide();
            lock = false;
        });
        responsePromise.error(function(data,status, headers, config) {
            console.log("AJAX Error.");
        }); 
    };
    
    $scope.loadTotal = function()
    {
        var responsePromise = $http({
                                method: 'POST',
                                url: base_url+"/order/gettotal",
                                data: $.param({"page" : page, "search" : search, "order_id" : order_id, "name" : name, "email" : email, "start_order" : start_order, "end_order": end_order, "transaction_id":transaction_id, "payment_type" : payment_type, "status" : status}),
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                                });
        responsePromise.success(function(data, status, headers, config) {
            $scope.totalData = parseInt(data);
        });
        
        
        responsePromise.error(function(data, status, headers, config) {
            console.log("AJAX Error");
        });
    }
    
    $scope.queryByDay = function (data){
        var now = new Date();
        var date = new Date(data.order_date.replace(/-/g, "/"));
        var minimum = Math.round((now - date)/( 24 * 3600 * 1000));
        
        if ($scope.Range == "" ) {
            return true;
        } else if (minimum <= parseInt($scope.Range)) {
            return true;
        }
        
        return false;
    };
    
    $scope.exportCurrent = function($event)
    {
        var data = $filter('filter')($scope.subscription, $scope.query);
        data = $filter('filter')(data, $scope.queryByDay);
        
        var json = JSON.stringify(data);
        var responsePromise = $http({
                                method: 'POST',
                                url: base_url+"/order/exportcsv",
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
                                url: base_url+"/order/exportcsv",
                                data: $.param({"page" : page, "search" : search, "order_id" : order_id, "name" : name, "email" : email, "start_order" : start_order, "end_order": end_order, "transaction_id":transaction_id, "payment_type" : payment_type, "status" : status}),
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

payment.filter("asDate", function () {
    return function (input) {
        return (input != "") ? new Date(input.replace(/-/g, "/")) : input;
    }
});

payment.directive('scroll', [function() {
  return {
    link: function($scope, element, attrs) {
      // ng-repeat delays the actual width of the element.
      // this listens for the change and updates the scroll bar
      function scrollListener() {
        var maxScroll = $("table#transaction_table tbody")[0].scrollHeight - $("table#transaction_table tbody").outerHeight() - 100;
        if ($(element).scrollTop() >= maxScroll && $(element).scrollTop() != 0)
        {
            offset = $("table#transaction_table tbody tr").length;
            last_offset = (last_offset == "-") ? offset : last_offset;
            if (last_offset >= limit_row && lock == false)
            {
                page++;
                $scope.loadMore(page);
                $scope.loading = true;
            }
            else
            {
                $(".bg-loading").hide();
            }
        }
        
        console.log($(element).scrollTop() + " " + maxScroll + " " + offset + " " + last_offset);
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
    $("#advanced_search_button").click(function()
    {
         $("#advanced_search_container").slideToggle();
    });
});