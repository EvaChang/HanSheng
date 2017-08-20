/**
 * Created by Administrator on 2017/5/25.
 */
app.controller('BankController',function ($scope,myhttp,$modal,toaster) {
    myhttp.getData('/index/finance/bank','GET')
            .then(function (res) { $scope.banks=res.data;});
    $scope.bankAdd=function () {
        var modalInstance = $modal.open({
            templateUrl: 'admin/finance/bankAdd.html',
            controller: 'bankAddController',
            size:'sm'
        });
        modalInstance.result.then(function (bank) {
           myhttp.getData('/index/finance/bankAdd','POST',bank)
                  .then(function (res) {
                      switch(res.data.result){
                          case 1:
                              myhttp.getData('/index/finance/bank','GET')
                                  .then(function (res) { $scope.banks=res.data;});
                              break;
                          case 3:
                              toaster.pop('info','该银行名称已经存在，请核对！');
                              break;
                          default:
                              toaster.pop('error','添加银行失败，请刷新页面后重新添加！');
                      }
           })
        });
    };
    $scope.setWeight=function (bank) {
        myhttp.getData('/index/finance/bankUpdate','GET',{bank_id:bank.bank_id})
            .then(function (res) {
               if(res.data.result==1){
                   myhttp.getData('/index/finance/bank','GET')
                       .then(function (res) { $scope.banks=res.data;});
               }else {
                   toaster.pop('error','修改失败，请重新设置！');
               }
            });
    }
});
app.controller('bankAddController',function ($scope,$modalInstance) {
    $scope.add=function () {
        $modalInstance.close($scope.bank);
    }
});
app.controller('BankDetailController',function ($scope,myhttp,$stateParams,$filter,toaster) {
    var sdate='';
    var edate='';
    $scope.bank_name=$stateParams.bank_name;
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        if($scope.sdate&&$scope.sdate){
            if($scope.sdate>$scope.edate){
                toaster.pop('info','结束日期不能早于开始日期');
                $scope.sdate='';
                $scope.edate='';
                return false;
            }
            sdate=$filter('date')($scope.sdate, "yyyy-MM-dd 00:00:00");
            edate=$filter('date')($scope.edate, "yyyy-MM-dd 23:59:59");
        }
        myhttp.getData('/index/finance/bankDetail','GET',
            {
                page:page,
                search:filter,
                bank_id:$stateParams.bank_id,
                edate:edate,
                sdate:sdate
            })
            .then(function (res) {
               // alert(res.data.total_count);
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
            });
    };
    $scope.query($stateParams.page,$stateParams.search,$stateParams.bank_id);
    $scope.search=function () {
        $scope.query(1,$scope.search_context);
    };
});
app.controller('TransferController',function ($scope,myhttp,toaster,$state) {
    $scope.check=false;
    $scope.trans={outBank:{},inBank:{}};
    myhttp.getData('/index/finance/bank','GET')
        .then(function (res) {
            $scope.banks=res.data;
            $scope.trans.outBank=$scope.banks[0].bank_id;
            $scope.trans.inBank=$scope.banks[0].bank_id;
        });
    $scope.transfer=function () {
        $scope.check=true;
        myhttp.getData('/index/finance/transfer','POST',{trans:$scope.trans})
            .then(function(res){
                $scope.check=false;
                if(res.data.result==1){
                    toaster.pop('success','转账成功！');
                    $state.go('app.finance.bank');
                }else{
                    toaster.pop('error','转账失败，请联系管理员！');
                }
            });
    }
});
app.controller('SubjectController',function($scope,toaster,$rootScope,$modal,myhttp,$stateParams){
    $scope.query=function(page){
        if(!page) page=1;
        myhttp.getData('/index/finance/subject','GET',{page:page})
            .then(function(res){
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
                    data.pages.push(i);
                $scope.data=data;
            });
    };
    $scope.query($stateParams.page,$stateParams.search);
    $scope.subjectModel=function(subject){
        if(!subject) subject={subject_id:0,subject_type:0};
        var myscope = $rootScope.$new();
        myscope.subject=subject;
       // console.log(myscope.subject);
        var modalInstance = $modal.open({
            templateUrl: 'admin/finance/subjectUpdate.html',
            controller: 'SubjectUpdateController',
            size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function(res){
            if(res==1){
                toaster.pop('success','添加修改成功！');
            }else {
                toaster.pop('error','添加修改失败，请联系管理员！');
            }
            $scope.query();
        });
    }
});
app.controller('SubjectUpdateController',function($scope,myhttp,$modalInstance){
    $scope.check=false;
    $scope.save=function() {
        $scope.check=true;
        myhttp.getData('/index/finance/subjectUpdate', 'POST', $scope.subject)
            .then(function (res) {
                $check=false;
                $modalInstance.close(res.data.result);
            });
    }
    $scope.cancel=function(){
        $modalInstance.dismiss('cancel');
    }
});
app.controller('IncomeController',function($scope,myhttp,$modal,$rootScope,toaster,$stateParams){
    $scope.query=function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/finance/income','GET',{page:page,search:filter})
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
            });
    }
    $scope.query($stateParams.page,$stateParams.search);
    $scope.search=function () {
       // alert();
        $scope.query(1,$scope.search_context);
    };
    $scope.incomeModel=function(account){
        if(!account) account={account_id:0}; //console.log(account);
        var myscope = $rootScope.$new();
        myscope.account=account;
        var modalInstance = $modal.open({
            templateUrl: 'admin/finance/incomeUpdate.html',
            controller: 'IncomeUpdateController',
            //size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function(res){
            if(res==1){
                toaster.pop('success','添加修改成功！');
            }else {
                toaster.pop('error','添加修改失败，请联系管理员！');
            }
            $scope.query();
        });
    }
    $scope.delete=function(data){
        var summary=data.summary.split(':');
        var myscope = $rootScope.$new();
        myscope.info=summary[1]+'的收入条目';
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
            scope:myscope
        });
        modalInstance.result.then(function () {
            myhttp.getData('/index/finance/deleteIncome','POST',{account_id:data.account_id,income:data.income,creditTable:data.creditTable,summary:data.summary,sale_detail_id:data.sale_detail_id})
                .then(function (res) {
                    if(res.data.result==1){
                        toaster.pop('success','删除成功！');
                        $scope.query(1,$scope.search_context);
                    }else
                        toaster.pop('error','删除失败！');
                });
        });
    }
});
app.controller('ConfirmController', ['$scope', '$modalInstance', function($scope, $modalInstance){
    $scope.ok = function () {
        $modalInstance.close();
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
}]);
app.controller('IncomeUpdateController',function($scope,myhttp,$modalInstance,toaster){
    $scope.check=false;
    $scope.account=$.extend($scope.account,{people:[]});
    if($scope.account.account_id!=0){
        var summary=$scope.account.summary.split(':');
        $scope.name=summary[1];
    }
    myhttp.getData('/index/finance/bankAndSubject','GET',{subject_type:0})
        .then(function (res) {
            $scope.banks=res.data.banks;
            $scope.subjects=res.data.subjects;
            $scope.account=$.extend($scope.account,{subject:$scope.subjects[0].subject_id,bank_id:0});
        });
    $scope.save=function() {
        if ($scope.account.account_id==0) {     //新增income
            if ($scope.account.bank_id == 0 && $scope.account.people.length == 0) {
                toaster.pop('info', '请输入联系人，或收款银行！');
                return false;
            }
            $scope.check=true;
            myhttp.getData('/index/finance/incomeAdd', 'POST', $scope.account)
                .then(function (res) {
                    $scope.check=false;
                    $modalInstance.close(res.data.result);
                });
        }else{                          //update income
            myhttp.getData('/index/finance/incomeUpdate', 'POST',{account_id:$scope.account.account_id,income:$scope.account.income,summary:$scope.account.summary,subjecct:$scope.account.subjecct})
                .then(function (res) {
                    $scope.check=false;
                    $modalInstance.close(res.data.result);
                });
       }

    }
    $scope.cancel=function(){
        $modalInstance.dismiss('cancel');
    }
    $scope.query_people=function(people){
        if(people!='')
            myhttp.getData('/index/finance/peopleSearch', 'GET', {search: people})
                .then(function (res) {
                    $scope.peoples = res.data;
                });
    }
});
//cost
app.controller('CostController',function($scope,myhttp,$modal,$rootScope,toaster,$stateParams){
    $scope.query=function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/finance/cost','GET',{page:page,search:filter})
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
            });
    }
    $scope.query($stateParams.page,$stateParams.search);
    $scope.search=function () {
        // alert();
        $scope.query(1,$scope.search_context);
    };
    $scope.costModel=function(account){
        if(!account) account={account_id:0}; //console.log(account);
        var myscope = $rootScope.$new();
        myscope.account=account;
        var modalInstance = $modal.open({
            templateUrl: 'admin/finance/costUpdate.html',
            controller: 'CostUpdateController',
            //size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function(res){
            if(res==1){
                toaster.pop('success','添加修改成功！');
            }else {
                toaster.pop('error','添加修改失败，请联系管理员！');
            }
            $scope.query();
        });
    }
    $scope.delete=function(data){
        var summary=data.summary.split(':');
        var myscope = $rootScope.$new();
        myscope.info=summary[1]+'的支出条目';
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
            scope:myscope
        });
        modalInstance.result.then(function () {
            myhttp.getData('/index/finance/deleteIncome','POST',{account_id:data.account_id,cost:data.cost,creditTable:data.creditTable,summary:data.summary,sale_detail_id:data.sale_detail_id})
                .then(function (res) {
                    if(res.data.result==1){
                        toaster.pop('success','删除成功！');
                        $scope.query(1,$scope.search_context);
                    }else
                        toaster.pop('error','删除失败！');
                });
        });
    }
});
app.controller('CostUpdateController',function($scope,myhttp,$modalInstance,toaster){
    $scope.check=false;
    $scope.account=$.extend($scope.account,{people:[]});
    if($scope.account.account_id!=0){
        var summary=$scope.account.summary.split(':');
        $scope.name=summary[1];
    }
    myhttp.getData('/index/finance/bankAndSubject','GET',{subject_type:1})
        .then(function (res) {
            $scope.banks=res.data.banks;
            $scope.subjects=res.data.subjects;
            $scope.account=$.extend($scope.account,{subject:$scope.subjects[0].subject_id,bank_id:0});
        });
    $scope.save=function() {

       // console.log($scope.account.people);
        if ($scope.account.account_id==0) {     //新增cost
            if ($scope.account.bank_id == 0 && $scope.account.people.length == 0) {
                toaster.pop('info', '请输入联系人，或收款银行！');
                return false;
            }
            $scope.check=true;
            myhttp.getData('/index/finance/costAdd', 'POST', $scope.account)
                .then(function (res) {
                    $scope.check=false;
                    $modalInstance.close(res.data.result);
                });
        }else{                          //update cost
            myhttp.getData('/index/finance/costUpdate', 'POST',{account_id:$scope.account.account_id,cost:$scope.account.cost,summary:$scope.account.summary,subjecct:$scope.account.subjecct})
                .then(function (res) {
                    $scope.check=false;
                    $modalInstance.close(res.data.result);
                });
        }

    }
    $scope.cancel=function(){
        $modalInstance.dismiss('cancel');
    }
    $scope.query_people=function(people){
        if(people!='')
            myhttp.getData('/index/finance/peopleSearch', 'GET', {search: people})
                .then(function (res) {
                    $scope.peoples = res.data;
                });
    }
});