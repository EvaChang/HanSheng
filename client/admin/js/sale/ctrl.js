/**
 * Created by Administrator on 2017/5/22 0022.
 */
'use strict';
app.controller('MemberController', function($scope,$http, $resource,myhttp,$stateParams,ngDialog) {
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/sale/member','GET',{page:page,search:filter})
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
    };
    $scope.query($stateParams.page,$stateParams.search);
    $scope.search=function () {
        $scope.query(1,$scope.search_context);
    };
    $scope.memberDialog=function(data) {
        ngDialog.open({
            template: 'admin/sale/memberUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
           // controller:'MemberUpdateController',
            data:data,
            preCloseCallback: function(value){
                switch(value)
                {
                    case 3:
                        ngDialog.open({
                            template:'<div><i class="icon-info text-info-lter"></i>  该客户姓名已经存在，请核对...</div>',
                            plain: true
                        });
                        break;
                    case 0:
                        ngDialog.open({
                            template:'<div><i class="icon-info text-error" ></i>  客户更新失败，请刷新后再试!</div>',
                            plain: true
                        });

                }
            }
        });
    };
});
app.controller('MemberUpdateController',function ($scope,myhttp) {
    var check=true;
    $scope.data=$scope.ngDialogData;
    $scope.py=function (data) {
        $scope.data.member_sn=makePy(data).toString();
    };
    $scope.updateMember=function () {
        check=false;
        if(typeof( $scope.data.member_id)=="undefined") $scope.data.member_id='' ;
        myhttp.getData('/index/sale/memberUpdate','POST',$scope.data)
            .then(function (res) {
                check=true;
                $scope.closeThisDialog(res.data.result);
            });
    }
});
app.controller('SaleController',function ($scope,myhttp,toaster,ngDialog,$state,$timeout,$localStorage) {
    $scope.members=[];
    $scope.pay={bank:{}};
    $scope.data={member:[]};      //此处必须定义成数组，angularjs's bug
    $scope.rows=new Array(5);
    $scope.check=false;
    $scope.$on('ngRepeatFinished', function() {
        angular.element('#member').find('input').focus();
    });
    myhttp.getData('/index/finance/bank','GET')
        .then(function (res) {
            $scope.banks=res.data;
            $scope.pay.bank=$scope.banks[0].bank_id;
        });
    $scope.query_member=function (search) {
        if(search!='')
            myhttp.getData('/index/sale/memberSearch', 'GET', {page: 1, search: search})
                .then(function (res) {
                    $scope.members = res.data.member;
                });
    };
    $scope.memberDialog=function() {
        ngDialog.open({
            template: 'admin/sale/memberUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
            controller:'MemberUpdateController',
            preCloseCallback: function(value){
                switch(value)
                {
                    case 3:
                        toaster.pop('info','该客户姓名已经存在，请核对...');
                        break;
                    case 0:
                        toaster.pop('error','客户更新失败，请刷新后再试!');
                }
            }
        });
    };

    $scope.query_goods=function (search) {
        if(search!='') {
            myhttp.getData('/index/setting/goodsQuery', 'GET', {search: search})
                .then(function (res) {
                    $scope.goods = res.data;
                });
        }
    }
    $scope.cal=function (index) {
        if($scope.rows[index]==null){toaster.pop('info','请完善销售信息！');return false;}
        $scope.rows[index].sum=Math.round($scope.rows[index].number*$scope.rows[index].price);
        var sum=0,num=0;
        angular.forEach($scope.rows,function (row) {
            num+=row.number;
            sum+=row.sum;
        })
        $scope.ttl_number=num;
        $scope.data.ttl_sum=sum;
    }
    $scope.cal_sum=function () {
        var sum=0;
        angular.forEach($scope.rows,function (row) {
            sum+=row.sum;
        })
        $scope.data.ttl_sum=sum;
    }
    $scope.addRow=function () {
        $scope.rows.push({number:null,price:null,sum:null});
    }
    $scope.delRow=function (index) {
        $scope.rows.splice(index,1);
    }
    $scope.save=function (back) {
        if($scope.data.member==''){toaster.pop('info','请输入客户');return false;}
        var detail=[];
        var rowcheck=false;
        angular.forEach($scope.rows,function (row) {
            if(isNaN(row.sum)) rowcheck=true;
            detail.push(row);
        });
        if(rowcheck){toaster.pop('info','请完善销售信息');return false;}
        $scope.check=true;
        myhttp.getData('/index/sale/sale','POST',
            {
                'data':$scope.data,
                'pay':$scope.pay,
                'detail':detail,
                'back':back
            })
            .then(function (res) {
                $scope.check=false;
                switch(res.data.result) {
                    case 1:
                        toaster.pop('success', '保存成功！');
                        $state.go('app.sale.index',null,{reload:true});
                        break;
                    case 0:
                        toaster.pop('info','请刷新，重新输入！');
                        break;
                    default :
                        toaster.pop('error','系统错误，请联系管理员');

                }
            });
    }
    $scope.getPrice=function (item,index) {
        if($scope.data.member!='') {
            myhttp.getData('/index/sale/getPrice','GET',
                {
                    'goods_id':item.goods_id,
                    'member_id':$scope.data.member.member_id})
                .then(function (res) {
                    //alert(res.data.result);
                    $scope.rows[index].price= parseFloat(res.data.result).toFixed(2);
                    $scope.cal(index);
                    $scope.rows[index].stock= parseInt(res.data.stock);
                    angular.element('#saleTable tbody tr:eq('+index+') td:eq(2) ').find('input').focus();
                });
        }
    }
    $scope.getCredit=function (item) {
        myhttp.getData('/index/sale/getMemberCredit','GET',{'member_id':item.member_id})
            .then(function (res) {
                $scope.credit=res.data.credit;
                angular.element('#saleTable tbody tr td:eq(1) ').find('input').focus();
            });
    }
    $scope.printCredit=function (list) {
        var url = $state.href('print.saleCredit',{member_id:list.member_id,member_name:list.member_name});
        // window.open('admin/sale/printCredit.html','_blank');
        window.open(url,'_blank');
    }
    $scope.next=function (e,index) {
        index++;
        var keycode = window.event?e.keyCode:e.which;
        if(keycode==13){
            $timeout(function () {
                angular.element('#saleTable tbody tr:eq(' + index + ') td:eq(1) ').find('input').focus();
            },200,false);
            if(index==0) $scope.save(1);
        }
        if(keycode==42) $timeout(function () {angular.element('#paid_sum').focus();},200,false);
    }
    $scope.print=function(){
        if($scope.data.member.length==0) return false;
        var credits=[];
        var num=0;var sum=0;
        angular.forEach($scope.rows, function(item) {
           // alert(angular.isDefined(item.good));
            if(angular.isDefined(item.good)) {
                var credit = {
                    goods_name: item.good.goods_name,
                    price: item.price,
                    number: item.number,
                    sum: item.sum,
                    unit: item.good.unit
                };
                credits.push(credit);
                num += item.number;
                sum += item.sum;
            }
        });
        $localStorage.ttl={number:num,sum:sum};
        $localStorage.credits=credits;
        var url = $state.href('print.sale',{sale_id:0,member_name:$scope.data.member.member_name});
        window.open(url,'_blank');
    }

});
app.controller('SaleListController',function ($scope,myhttp,$stateParams,$filter,$modal,$rootScope,toaster,$state) {
    var sdate='';
    var edate='';
    $scope.query = function(page,filter,urlSdate,urlEdate){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
            //$stateParams.page=page;
        }
        if(urlEdate&&urlSdate){
            sdate=urlSdate;
            edate=urlEdate;
        }
        if(!filter) filter='';
        myhttp.getData('/index/sale/saleList','GET',
            {
                page:page,
                search:filter,
                sdate:sdate,
                edate:edate
            })
            .then(function (res) {
                // console.log(res.data);return false;
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
                $scope.search_context = filter;
                $scope.edate=$filter('limitTo')(edate, 10);
                $scope.sdate=$filter('limitTo')(sdate, 10);
                $scope.data=data;
            });
    };
    $scope.query($stateParams.page,$stateParams.search,$stateParams.sdate,$stateParams.edate);
    $scope.search=function () {
        if($scope.sdate&&$scope.sdate) {
            if ($scope.sdate > $scope.edate) {
                toaster.pop('info', '结束日期不能早于开始日期');
                $scope.sdate = '';
                $scope.edate = '';
                return false;
            }
            sdate = $filter('date')($scope.sdate, "yyyy-MM-dd 00:00:00");
            edate = $filter('date')($scope.edate, "yyyy-MM-dd 23:59:59");
            //console.log(sdate);
            $state.go('app.sale.list', {page:1,search: $scope.search_context, sdate: sdate, edate: edate});
        }else{
            $state.go('app.sale.list',{page:1,search: $scope.search_context,sdate:null, edate:null});
        }
    };
    $scope.pageQuery=function (page,search,sdate,edate) {
        $state.go('app.sale.list', {page:page,search: $scope.search_context, sdate: sdate, edate: edate});
    }
    //删除销售单
    $scope.delete=function (list) {
        var myscope = $rootScope.$new();
        myscope.info=list.member_name+'的销售单';
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
           // size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function () {
            myhttp.getData('/index/sale/deleteSale','POST',{sale_id:list.sale_id,info:list.member_name+':'+list.sum})
                .then(function (res) {
                    if(res.data.result==1){
                        toaster.pop('success','删除成功！');
                        $state.go($rootScope.previousState,$rootScope.previousStateParams);
                    }else
                        toaster.pop('error','删除失败！');
                });
        });
    }
    $scope.printSale=function (list) {
        var url = $state.href('print.sale',{sale_id:list.sale_id,member_name:list.member_name,time:list.time});
        window.open(url,'_blank');
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
app.controller('SaleDetailController',function ($scope,myhttp,$stateParams,toaster,$rootScope,$modal,$state) {
    $scope.member_name=$stateParams.member_name;
    $scope.finish=$stateParams.finish;
    myhttp.getData('/index/sale/saleDetail','GET',{sale_id:$stateParams.sale_id})
        .then(function (res) {
            $scope.details=res.data;
            var sum=0,num=0;
            angular.forEach($scope.details,function (row) {
                num+=Number(row.number);
                sum+=row.sum;
            });
            $scope.ttl_number=num;
            $scope.ttl_sum=sum;
        });
    $scope.edit=function (index) {
        if($scope.details[index].editing){
            if(isNaN($scope.details[index].sum)||($scope.details[index].sum==0)){toaster.pop('info','请完善修改信息！');return false;}
            var data={};
            data.sale_detail_id=$scope.details[index].sale_detail_id;
            data.price=$scope.details[index].price;
            data.number=$scope.details[index].number;
            data.sum=$scope.details[index].sum;
            data.sale_id=$scope.details[index].sale_id;
            myhttp.getData('/index/sale/saleDetailUpdate','POST',data)
                .then(function (res) {
                    // console.log(res.data);
                    if(res.data.result==1){
                        toaster.pop('success','修改成功！');
                        $scope.details[index].editing=false;
                    }
                });
        }
        $scope.details[index].editing=true;
    }
    $scope.myBack=function(){
        // console.log($rootScope.previousStateParams);
         $state.go($rootScope.previousState,$rootScope.previousStateParams);
    }
    $scope.cal=function (index) {
        $scope.details[index].sum=Math.round($scope.details[index].number*$scope.details[index].price);
        var sum=0,num=0;
        angular.forEach($scope.details,function (row) {
            // alert(num);
            num+=Number(row.number);
            sum+=row.sum;
        });
        $scope.ttl_number=num;
        $scope.ttl_sum=sum;
    }
    $scope.cal_sum=function () {
        var sum=0;
        angular.forEach($scope.details,function (row) {
            sum+=row.sum;
        });
        $scope.ttl_sum=sum;
    }
    $scope.delete=function (detail) {
        var myscope = $rootScope.$new();
        myscope.info=$scope.member_name+'的销售单';
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
            // size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function () {
            myhttp.getData('/index/sale/saleDetailDelete','POST',
                {sale_detail_id:detail.sale_detail_id,sale_id:detail.sale_id})
                .then(function (res) {
                    if(res.data.result==1){
                        toaster.pop('success','删除成功！');
                        myhttp.getData('/index/sale/saleDetail','GET',{sale_id:$stateParams.sale_id})
                            .then(function (res) {
                                $scope.details=res.data;
                                var sum=0,num=0;
                                angular.forEach($scope.details,function (row) {
                                    num+=Number(row.number);
                                    sum+=row.sum;
                                });
                                $scope.ttl_number=num;
                                $scope.ttl_sum=sum;
                            });
                    }
                });
        });

    }
});
app.controller('McreditController',function ($scope,myhttp,toaster,$stateParams,$modal,$rootScope,$state
) {
    $scope.query_ttl=function () {
        myhttp.getData('/index/sale/ttlCredit','GET')
            .then(function (res) {
                $scope.ttlCredit=res.data;
            });
    };
    $scope.query_ttl();
    $scope.query_credit=function (page) {
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        myhttp.getData('/index/sale/credit','GET',{page:page})
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
                    data.pages.push(i);
                $scope.data=data;
            });
    };
    $scope.query_credit($stateParams.page);
    $scope.more=function (list) {
        var myscope = $rootScope.$new();
        myscope.member=list;
        var modalInstance = $modal.open({
            templateUrl: 'admin/sale/creditDetail.html',
            controller: 'saleCreditDetailController',
            size:'lg',
            scope:myscope
        });
        modalInstance.result.then(function (res) {
            $scope.query_credit($stateParams.page);
            $scope.query_ttl();
            if(res==1){
                toaster.pop('success','保存成功!');
            } else{
                toaster.pop('error','保存失败，请联系管理员！');
            }
        });
    }
    //打印对账单
    $scope.printCredit=function (list) {
        var url = $state.href('print.saleCredit',{member_id:list.member_id,member_name:list.member_name});
       // window.open('admin/sale/printCredit.html','_blank');
        window.open(url,'_blank');
    }
});

app.controller('saleCreditDetailController',function ($scope,myhttp,$modalInstance,$modal,$rootScope) {
    $scope.pay={bank:{}};
    $scope.check=false;
    myhttp.getData('/index/sale/creditDetail','GET',{member_id:$scope.member.member_id})
        .then(function (res) {
            $scope.credits=res.data.credit;
            $scope.banks=res.data.bank;
            $scope.pay.bank=$scope.banks[0].bank_id;
        });
    $scope.cancel=function () {
        $modalInstance.dismiss('cancel');
    };
    var selected = false;
    $scope.selectAll = function(){
        selected = !selected;
        var sum=0;
        angular.forEach($scope.credits,function(item) {
            item.selected = selected;
            if(!selected){$scope.pay.paid_sum=0;return false; }
            if ((item.type== 'S') || (item.type =='B')) {
                sum += item.sum;
            } else {
                sum += item.ttl;
            }
            $scope.pay.paid_sum = sum;
        });
    };
    $scope.cal=function () {
        var sum=0;
        var index=-1;
        angular.forEach($scope.credits,function(item){
            if(item.selected){
               // if(item.type!='S') {
                    sum+=item.ttl;
               // }else{
              //  if((index<0)||(item.time!=$scope.credits[index].time))
               //         sum += item.ttl;
               // }
            }
            index++;
        });
        $scope.pay.paid_sum=sum;
    };
    $scope.clean=function () {
        angular.forEach($scope.credits,function(item){
            item.selected=false;
        });
    };
    $scope.payment=function () {
        var saleID=[];
        angular.forEach($scope.credits,function(item){
            if(item.selected)saleID.push(item.sale_id);
        });
        $scope.check=true;
       // if($scope.pay.paid_sum){
            myhttp.getData("/index/sale/payment",'POST',{pay:$scope.pay,saleID:saleID,member:$scope.member})
                .then(function (res) {
                    //console.log(res.data);
                    $scope.check=false;
                    $modalInstance.close(res.data.result);
                })

        //}
    };
    $scope.carryOver=function () {
        var saleID=[];
        var count=0;
        angular.forEach($scope.credits,function(item){
            if(item.selected){saleID.push(item.sale_id);count++;}
        });
        if(count>0){
            $scope.check=true;
            var remark=$modal.open({
                templateUrl: 'admin/carryOverRemark.html',
                controller: 'remarkController'
            });
            remark.result.then(function (remark) {
                myhttp.getData('/index/sale/carryOver','POST',{saleID:saleID,summary:remark,member_id:$scope.member.member_id})
                    .then(function (res) {
                        $scope.check=false;
                        $modalInstance.close(res.data.result);
                    });
            });
        }
    }
    $scope.cfPrint=function (sale_id) {
        var myscope = $rootScope.$new();
        myscope.sale_id=sale_id;
        var remark=$modal.open({
            templateUrl: 'admin/sale/cfPrint.html',
            controller: 'cfPrintController',
            scope:myscope
        });

    }
});
app.controller('cfPrintController',function ($modalInstance,$scope,myhttp,$rootScope,$modal) {
    myhttp.getData('/index/sale/cfPrint','GET',{cf:$scope.sale_id})
        .then(function (res) {
            $scope.credits = res.data.credit;
        });
    $scope.cfPrint=function (sale_id) {
        var myscope = $rootScope.$new();
        myscope.sale_id = sale_id;
        var remark = $modal.open({
            templateUrl: 'admin/sale/cfPrint.html',
            controller: 'cfPrintController',
            scope: myscope
        });
    }
    $scope.cancel=function () {
        $modalInstance.dismiss('cancel');
    }
});
app.controller('remarkController',function ($modalInstance,$scope) {
    $scope.ok=function () {
        $modalInstance.close($scope.remark);
    }
});