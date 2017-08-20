/**
 * Created by Administrator on 2017/7/14 0014.
 */
app.controller('DashBoardController', function ($scope,myhttp,$rootScope,$modal) {
    myhttp.getData('/index/statistics/dashGetSum','GET')
        .then(function(res){
            $scope.sum=res.data.sum;
            $scope.back=res.data.back;
            $scope.onWay=res.data.onWay;
        });
    $scope.show=function(type){
        var myscope = $rootScope.$new();
        myscope.type=type;
        $modal.open({
            templateUrl: 'admin/statistics/saleToday.html',
            controller: 'SaleTodayController',
            // size:'sm',
            scope:myscope
        });
    };
    $scope.more=function (list) {
        var myscope = $rootScope.$new();
        myscope.purchase=list;
        $modal.open({
            templateUrl: 'admin/statistics/onWay.html',
            controller: 'onWayController',
            scope:myscope
        });
    }
    $scope.receiveGoods=function (list) {
        var myscope = $rootScope.$new();
        myscope.purchase=list;
        var mymodel=$modal.open({
            templateUrl: 'admin/statistics/receiveGoods.html',
            controller: 'receiveGoodsController',
            scope:myscope
        });
        mymodel.result.then(function () {
            myhttp.getData('/index/statistics/receiveGoods','GET',{purchase_id:list.purchase_id,finish:list.finish})
                .then(function (res) {
                    $scope.onWay=res.data.onWay;
                })
        });
    }
});
app.controller('SaleTodayController',function ($scope,myhttp,$modalInstance) {
    $scope.query=function (page) {
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        myhttp.getData('/index/statistics/saleToday','GET',{page:page,type:$scope.type})
            .then(function (res) {
                var data=res.data;
                data.page_index = page;
                data.pages = [];    //页签表
                var N = 5;          //每次显示5个页签
                data.page_count=Math.ceil(data.total_count/10);
                var s = Math.floor(page/N)*N;
                if(s==page)s-=N;
                s += 1;
                var e = Math.min(data.page_count,s+N-1);
                for(var i=s;i<=e;i++)
                    data.pages.push(i)
                $scope.data=data;
            });
    };
    $scope.query(1);
    $scope.ok = function () {
        $modalInstance.close();
    };
});
app.controller('onWayController',function ($scope,myhttp,$modalInstance) {
    $scope.query=function (page) {
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        myhttp.getData('/index/statistics/onWay','GET',{page:page,purchase_id:$scope.purchase.purchase_id})
            .then(function (res) {
                var data=res.data;
                data.page_index = page;
                data.pages = [];    //页签表
                var N = 5;          //每次显示5个页签
                data.page_count=Math.ceil(data.total_count/10);
                var s = Math.floor(page/N)*N;
                if(s==page)s-=N;
                s += 1;
                var e = Math.min(data.page_count,s+N-1);
                for(var i=s;i<=e;i++)
                    data.pages.push(i)
                $scope.data=data;
            });
    };
    $scope.query(1);
    $scope.ok = function () {
        $modalInstance.close();
    };
});
app.controller('receiveGoodsController',function ($scope,myhttp,$modalInstance) {
    $scope.ok = function () {
        $modalInstance.close();
    };
    $scope.cancel=function () {
        $modalInstance.dismiss();
    }
});