/**
 * Created by Administrator on 2017/6/17 0017.
 */
app.controller('printSaleCreditController',function ($scope,$stateParams,myhttp) {
    $scope.doPrint=function () {
        if(!!window.ActiveXObject || "ActiveXObject" in window){
            try {
                var myDoc = {
                    settings:{topMargin:100,   //100,代表10毫米
                        leftMargin:20,
                        bottomMargin:500,
                        rightMargin:10},
                    documents: document,
                    /*
                     要打印的div 对象在本文档中，控件将从本文档中的 id 为 'page1' 的div对象，
                     作为首页打印id 为'page2'的作为第二页打印            */
                    copyrights: '杰创软件拥有版权  www.jatools.com' // 版权声明,必须
                };
                document.getElementById("jatoolsPrinter").print(myDoc, false); // 直接打印，不弹出打印机设置对话框
                window.close();
            }catch (e) {
                //  alert(e.name + ": " + e.message + "。可能是没有安装相应的控件或插件,请下载驱动程序!");
                window.open('vendor/jatoolsPrinter_free.zip','_self');
            }
        }else {
            window.print();
            window.close();
        }
    };
    $scope.member_name=$stateParams.member_name;
    $scope.now = new Date();
    myhttp.getData('/index/sale/printCredit','GET',{member_id:$stateParams.member_id})
        .then(function (res) {
            $scope.info=res.data.info;
            $scope.ttl=res.data.ttl;
            $scope.credits=res.data.credit;
            $scope.$on('ngRepeatFinished', function() {
                $scope.doPrint();
            });
        });
});
app.controller('printSaleController',function ($scope,$stateParams,myhttp,$localStorage,$filter) {
    $scope.doPrint=function () {
        if(!!window.ActiveXObject || "ActiveXObject" in window){
            try {
                var myDoc = {
                    settings:{topMargin:100,   //100,代表10毫米
                        leftMargin:20,
                        bottomMargin:500,
                        rightMargin:10},
                    documents: document,
                    /*
                     要打印的div 对象在本文档中，控件将从本文档中的 id 为 'page1' 的div对象，
                     作为首页打印id 为'page2'的作为第二页打印            */
                    copyrights: '杰创软件拥有版权  www.jatools.com' // 版权声明,必须
                };
                document.getElementById("jatoolsPrinter").print(myDoc, false); // 直接打印，不弹出打印机设置对话框
                window.close();
            }catch (e) {
                //  alert(e.name + ": " + e.message + "。可能是没有安装相应的控件或插件,请下载驱动程序!");
                window.open('vendor/jatoolsPrinter_free.zip','_blank');
            }
        }else {
            window.print();
            window.close();
        }
    };
    $scope.member_name=$stateParams.member_name;
    //$scope.now = $stateParams.time;
    if($stateParams.sale_id==0){
        $scope.now=$filter('date')(new Date(),'yyyy-MM-dd');
        myhttp.getData('/index/sale/printInfo','GET')
            .then(function(res){
                $scope.info = res.data.info;
                $scope.credits = $localStorage.credits;
                $scope.ttl = $localStorage.ttl;
                $scope.$on('ngRepeatFinished', function () {
                    $scope.doPrint();
            });
        });
    }else {
        myhttp.getData('/index/sale/printSale', 'GET', {sale_id: $stateParams.sale_id})
            .then(function (res) {
                $scope.now = $stateParams.time;
                $scope.info = res.data.info;
                $scope.ttl = res.data.ttl;
                $scope.credits = res.data.credit;
                $scope.$on('ngRepeatFinished', function () {
                    $scope.doPrint();
                });
            });
    }
});
