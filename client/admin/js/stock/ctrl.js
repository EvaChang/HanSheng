/**
 * Created by Administrator on 2017/6/21 0021.
 */
app.controller('StockController',function ($scope,myhttp,$stateParams,toaster,$timeout,$rootScope,$modal) {
    myhttp.getData('/index/setting/store','GET').then(function (res) {
        $scope.stores=res.data.store;
        $scope.stores.unshift({'store_id':0,'store_name':"全部仓库"});
        $scope.storeId=0;
        $scope.inorder=2;
        toaster.pop('info',"载入中...",'',5000);
        $scope.query($stateParams.page,$stateParams.search);
    });
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/stock/stockList','GET',{
            page:page,
            search:filter,
            inorder:$scope.inorder,
            store_id:$scope.storeId,
            orderby:angular.isDefined($scope.order)?$scope.order:''
        }).then(function (res) {
                toaster.clear();
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
                $scope.search_context = filter;
                $scope.data=data;
                backup=data.stock;
            });
    };

    $scope.search=function () {
        toaster.pop('info',"载入中...",'',5000);
        $timeout(function () {
            $scope.query(1,$scope.search_context);
        },200,false);

    };
    $scope.delStock=function(stock){
        var info="";
        if($scope.storeId!=0){
            angular.forEach($scope.stores,function(item){
                if($scope.storeId==item.store_id) info=item.store_name+"的";
        });
        }else info="所有仓库的";
        var myscope = $rootScope.$new();
        myscope.info=info+stock.goods_name;
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
            scope:myscope
        });
        modalInstance.result.then(function () {
            toaster.pop('info',"删除中请稍后...",'', 50000);
            myhttp.getData('/index/stock/delStock','GET',{
                store_id:$scope.storeId,
                info:info+stock.goods_name,
                goods_id:stock.goods_id,
                inorder:stock.inorder
            })
                .then(function(res){
                    toaster.clear();
                    if(res.data.result==1){
                       // toaster.pop('success','删除成功！');
                        $scope.query(1,$scope.search_context);
                    }else
                        toaster.pop('error','删除失败！');

                });

        });
    };

    $scope.updateStock=function (stock) {
        var myscope = $rootScope.$new();
        myscope.info=stock.goods_name;
        var modalInstance = $modal.open({
            templateUrl: 'admin/stock/updateStock.html',
            controller: 'ConfirmController',
            size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function (res) {
            toaster.pop('info','修改中...','',5000);
            myhttp.getData('/index/stock/updateStock','POST',{
                Dstock:res,
                stock:stock,
                store_id:$scope.storeId
            }).then(function (res) {
                toaster.clear();
                if(res.data.result==1){
                   // toaster.pop('success','修改成功！');
                    $scope.query(1,$scope.search_context);
                }else{
                    toaster.pop('error','修改失败！');
                }

            });
        });

    };
    $scope.stockDetail=function(stock){
        var myscope = $rootScope.$new();
        myscope.stock=stock;
        var modalInstance = $modal.open({
            templateUrl: 'admin/stock/stockDetail.html',
            controller: 'stockDetailController',
            scope:myscope
        });
    };
    $scope.exchange=function(stock){

    };
    $scope.transfer=function(stock){

    };
    $scope.carry=function(stock){

    }
});
app.controller('ConfirmController', ['$scope', '$modalInstance', function($scope, $modalInstance){
    $scope.ok = function () {
        $modalInstance.close();
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    $scope.updateStock=function (fx) {
        $modalInstance.close(fx*$scope.Dstock);
    }
}]);
app.controller('stockDetailController',['$scope', '$modalInstance', function($scope, $modalInstance){
    alert($scope.stock.goods_name);
    $scope.ok = function () {
        $modalInstance.dismiss('cancel');
    };
}]);