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
        myscope.storeId=$scope.storeId;
        var modalInstance=$modal.open({
            templateUrl: 'admin/stock/stockDetail.html',
            controller: 'stockDetailController',
            size:'lg',
            scope:myscope
        });
    };
    $scope.exchange=function(stock){
        var myscope = $rootScope.$new();
        myscope.stock=stock;
        myscope.trans={outStoreId:$scope.storeId};
        var stores=[];
        angular.forEach($scope.stores,function(item){
            if($scope.storeId==item.store_id) {
                myscope.store_name = item.store_name;
            }else{
               if(item.store_id!=0) stores.push(item);
            }
        });
        myscope.stores=stores;
        var modalInstance=$modal.open({
            templateUrl: 'admin/stock/exchange.html',
            controller: 'stockExchangeController',
           // size:'lg',
            scope:myscope
        });
        modalInstance.result.then(function(){
            $scope.query(1,$scope.search_context);
        });
    };
    $scope.transfer=function(stock){
        var myscope = $rootScope.$new();
        myscope.stock=stock;
        myscope.store_id=$scope.storeId;
        var modalInstance=$modal.open({
            templateUrl: 'admin/stock/transfer.html',
            controller: 'stockTransferController',
             size:'lg',
            scope:myscope
        });
        modalInstance.result.then(function(res){
            toaster.pop('success','转换成功!');
            $scope.query(1,$scope.search_context);
        });


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
app.controller('stockDetailController',['$scope', '$modalInstance','myhttp','toaster', function($scope, $modalInstance,myhttp,toaster){
    $scope.query=function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';

        myhttp.getData('/index/stock/stockDetail','POST',{
            page:page,
            search:filter,
            where:{goods_id:$scope.stock.goods_id,store_id:$scope.storeId,inorder:$scope.stock.inorder}
        })
            .then(function(res){
                toaster.clear();
                var data=res.data;
                data.page_index = page;
                data.pages = [];    //页签表
                var N = 5;          //每次显示5个页签
                data.page_count=Math.ceil(data.total_count/10)
                var s = Math.floor(page/N)*N;
                if(s==page)s-=N;
                s += 1;
                var e = Math.min(data.page_count,s+N-1);
                for(var i=s;i<=e;i++)
                    data.pages.push(i)
                $scope.search_context = filter;
                $scope.data=data;
            });
    };
    $scope.query();
    $scope.ok = function () {
        $modalInstance.close();
    };
}]);
app.controller('stockExchangeController',['$scope', '$modalInstance','myhttp','toaster', function($scope, $modalInstance,myhttp,toaster){
    $scope.stores=$scope.$parent.stores;
    $scope.trans=$scope.$parent.trans;
    $scope.store_name=$scope.$parent.store_name;
    $scope.trans.inStoreId=$scope.stores[0].store_id;
    $scope.check=false;
   $scope.exchange=function(){
       if($scope.trans.Dstock<=0||$scope.trans.Dstock==null) {toaster.pop('info','请输入正确的数量！');return false;}
       $scope.check=true;
       myhttp.getData('/index/stock/exchange','POST',{goods_id:$scope.stock.goods_id,inorder:$scope.stock.inorder,trans:$scope.trans})
           .then(function(res){
               $scope.check=false;
              if(res==1){
                  toaster.pop('success','调货成功！');
                  $modalInstance.close()
              }else toaster.pop('error','调货未成功，请联系管理员！');
           });

   };
    $scope.ok = function () {
        $modalInstance.dismiss();
    };
}]);
app.controller('stockTransferController',function($scope, $modalInstance,myhttp,toaster,$timeout){
    $scope.transIn={};
    $scope.check=false;
    $scope.ok = function () {
        $modalInstance.dismiss();
    };
    $scope.query_goods=function(search){
        if(search!='') {
            myhttp.getData('/index/setting/goodsQuery', 'GET', {search: search})
                .then(function (res) {
                    $scope.goods = res.data;
                });
        }
    };
    $scope.getUnit=function(item){
        $scope.units=new Array(0);
        $scope.transIn.unit='';
        myhttp.getData('/index/stock/getUnit','GET',{goods_id:item.goods_id,unit_id:item.unit_id})
           .then(function(res){
               $scope.units=res.data;
               $timeout(function () {
                   $scope.transIn.unit = item.unit_id;
                   angular.element('#transInNumber').focus();
               },100,false);
           });
    };
    $scope.transfer=function () {
        if(angular.isUndefined($scope.transIn.good)){
            toaster.pop('info','请输入商品信息！');return false;
        }
        if($scope.out.number==''||$scope.out.price==''||$scope.transIn.number==''||$scope.transIn.price==''){
            toaster.pop('info','请完善数量和价格！');return false;
        }
        $scope.check=true;
        myhttp.getData('/index/stock/transfer','POST',
            {
                out:{
                    goods_id:$scope.stock.goods_id,
                    goods_name:$scope.stock.goods_name,
                    number:$scope.out.number,
                    price:$scope.out.price,
                    unit_id:$scope.stock.unit_id,
                    inorder:$scope.stock.inorder
                },
                in:{
                    goods_id:$scope.transIn.good.goods_id,
                    goods_name:$scope.transIn.good.goods_name,
                    basic_unit:$scope.transIn.good.unit_id,
                    number:$scope.transIn.number,
                    price:$scope.transIn.price,
                    unit_id:$scope.transIn.unit
                },
                remark:$scope.remark,
                store_id:$scope.store_id
        }).then(function (res) {
            $scope.check=false;
            if(res.data.result==1){
                $modalInstance.close(1);
            }else
             toaster.pop('error','转换错误,请联系管理员');

        });
    };
});