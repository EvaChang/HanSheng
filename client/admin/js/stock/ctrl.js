/**
 * Created by Administrator on 2017/6/21 0021.
 */
app.controller('StockController',function ($scope,myhttp,$stateParams,toaster) {
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/stock/stockList','GET',{page:page,search:filter})
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
                $scope.search_context = filter;
                $scope.data=data;
                backup=data.stock;
            });
    };
    $scope.query($stateParams.page,$stateParams.search);
    $scope.search=function () {
        $scope.query(1,$scope.search_context);
    };
    $scope.editStock=function (index) {
        if($scope.data.stock[index].editing){
            $scope.data.stock[index].editing=false;
            $scope.find($scope.data.stock[index].stock_id,index);
            return false;
        }
        $scope.data.stock[index].editing=true;
    }
    $scope.find=function (stock_id,index) {
        myhttp.getData('/index/stock/stockList','GET',{stock_id:stock_id})
            .then(function (res) {
                $scope.data.stock[index].number=res.data.number;
                $scope.data.stock[index].sum=res.data.sum;
            });
    }
    $scope.updateStock=function (index) {
        var stock_id=$scope.data.stock[index].stock_id;
        var number=$scope.data.stock[index].number;
        myhttp.getData('/index/stock/stockUpdate','POST',{stock_id:stock_id,number:number})
            .then(function (res) {
                $scope.data.stock[index].editing=false;
                if(res.data.result==1){
                    toaster.pop('success','修改库存成功！');
                    $scope.data.stock[index].number=res.data.newStock.number;
                    $scope.data.stock[index].sum=res.data.newStock.sum;

                }else {
                    toaster.pop('error','修改库存失败！');
                    $scope.find($scope.data.stock[index].stock_id,index);
                }

            });
    }
});