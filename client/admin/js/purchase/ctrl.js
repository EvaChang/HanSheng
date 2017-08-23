/**
 * Created by Administrator on 2017/5/20.
 */
'use strict';
app.controller('SupplyController', function($scope,$http, $resource,myhttp,$stateParams,ngDialog,toaster) {
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/purchase/supply','GET',{page:page,search:filter})
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
    $scope.supplyDialog=function(data) {
        ngDialog.open({
            template: 'admin/purchase/supplyUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
            controller:'SupplyUpdateController',
            data:data,
            preCloseCallback: function(value){
                 switch(value)
                 {
                 case 3:
                     toaster.pop('info','该供应商姓名已经存在，请核对...');
                 break;
                 case 0:
                     toaster.pop('error','供应商更新失败，请刷新后再试!');
                 }
            }
        });
    };
});
app.controller('SupplyUpdateController',function ($scope,myhttp) {
    var check=true;
    $scope.data=$scope.ngDialogData;
    $scope.py=function (data) {
        $scope.data.supply_sn=makePy(data).toString();
    };
    $scope.updateSupply=function () {
        check=false;
        if(typeof( $scope.data.supply_id)=="undefined") $scope.data.supply_id='' ;
        myhttp.getData('/index/purchase/supplyUpdate','POST',$scope.data)
              .then(function (res) {
               check=true;
               $scope.closeThisDialog(res.data.result);
            });
    }
});
app.controller('PurchaseController',function ($scope,myhttp,toaster,ngDialog,$state,$timeout) {
    $scope.inorder='';
    $scope.inorderButton=true;
    $scope.supplies=[];
    $scope.pay={bank:{}};
    $scope.data={supply:[]};      //此处必须定义成数组，angularjs's bug
    $scope.rows=new Array(5);
    $scope.check=false;
    $scope.$on('ngRepeatFinished', function() {
        $timeout(function () {
            angular.element('#supply').find('input').focus();
        },200,false);

    });
    $scope.nextGood=function (item) {
        $timeout(function () {
        angular.element('#purchaseTable tbody tr td:eq(1) ').find('input').focus();
        },200,false);
    };
    myhttp.getData('/index/purchase/purchaseInfo','GET')
        .then(function (res) {
            $scope.banks=res.data.bank;
            $scope.pay.bank=$scope.banks[0].bank_id;
            $scope.stores=res.data.store;
            $scope.units=res.data.units;
        });
    $scope.query_supply=function (search) {
        if(search!='')
            myhttp.getData('/index/purchase/supplySearch', 'GET', {page: 1, search: search})
                .then(function (res) {
                    $scope.supplies = res.data.supply;
                });
    };
    $scope.supplyDialog=function() {
        ngDialog.open({
            template: 'admin/purchase/supplyUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
            controller:'SupplyUpdateController',
            preCloseCallback: function(value){
                switch(value)
                {
                    case 3:
                        toaster.pop('info','该供应商姓名已经存在，请核对...');
                        break;
                    case 0:
                        toaster.pop('error','供应商更新失败，请刷新后再试!');
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
        if($scope.rows[index]==null){toaster.pop('info','请完善进货信息！');return false;}
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
        if($scope.data.supply==''){toaster.pop('info','请输入供应商');return false;}
        var detail=[];
        var rowcheck=false;
        angular.forEach($scope.rows,function (row) {
            if(isNaN(row.sum)) rowcheck=true;
            if(angular.isDefined(row.good))
            detail.push({
                'goods_id':row.good.goods_id,
                'unit_check':row.good.unit_id.unit_id!=row.unit.unit_id,
                'number':row.number,
                'unit_id':row.unit.unit_id,
                'store_id':row.store_id,
                'price':row.price,
                'sum':row.sum
            });
        });
        if(rowcheck) {{toaster.pop('info','请完善进货信息!');return false;}}
        $scope.check=true;
        myhttp.getData('/index/purchase/purchase','POST',
            {
                'data':$scope.data,
                'pay':$scope.pay,
                'detail':detail,
                'back':back,
                'inorder':$scope.inorder
            })
            .then(function (res) {
                $scope.check=false;
                switch(res.data.result) {
                    case 1:
                    toaster.pop('success', '保存成功！');
                    $state.go('app.purchase.list');
                    break;
                    case 0:
                        toaster.pop('info','请刷新，重新输入！');
                        break;
                    default :
                        toaster.pop('error','系统错误，请联系管理员');

                }
            });
    }
    $scope.getUnit=function (item,index) {
          myhttp.getData('/index/purchase/getUnit', 'GET', {
              'goods_id': item.goods_id,
              'unit_id':item.unit_id,
              'supply_id':angular.isDefined($scope.data.supply)?$scope.data.supply.supply_id:0,
              'inorder':$scope.inorder
          })
          .then(function (res) {
              if(res.data.check==false){
                  if($scope.inorder=='') {
                      alert(item.goods_name + "在库存中为批次商品,请生成批次号或删除该商品库存!");
                  }else
                      alert(item.goods_name + "在库存中为普通商品,请删除"+$scope.inorder+"批次号或删除该商品库存!");
                  $scope.rows[index].good='';
                  return false;
              }
              $scope.inorderButton=false;
              $scope.units = res.data.unit;
              for (var j = 0; j < $scope.units.length; j++) {
                  if (($scope.units[j].unit_id == item.unit_id)) {
                      item.unit_id = $scope.units[j];
                      $scope.rows[index].unit = $scope.units[j];
                  }
              }
              $scope.rows[index].price= parseFloat(res.data.result);
              $scope.cal(index);
              $scope.rows[index].store_id = $scope.stores[0].store_id;
              angular.element('#purchaseTable tbody tr:eq('+index+') td:eq(2) ').find('input').focus();
          });

    };
    $scope.getPrice=function (item,index) {
        if($scope.data.supply!='') {
            myhttp.getData('/index/purchase/getPrice','GET',
                {
                    'goods_id':$scope.rows[index].good.goods_id,
                    'supply_id':$scope.data.supply.supply_id,
                    'unit_id':item.unit_id
                })
                .then(function (res) {
                    $scope.rows[index].price= parseFloat(res.data.result);
                    $scope.cal(index);
                });
        }
    }
    $scope.next=function (e,index) {
        var keycode = window.event?e.keyCode:e.which;
        index++;
        if(keycode==13){
            $timeout(function () {
                angular.element('#purchaseTable tbody tr:eq(' + index + ') td:eq(1) ').find('input').focus();
            },200,false);
            if(index==0) $scope.save(1);
        }
        if(keycode==42) $timeout(function () {angular.element('#paid_sum').focus();},200,false);
    }
    $scope.addInorder=function () {
        myhttp.getData('/index/purchase/getInOrder','GET').then(function (result) {
            $scope.inorder=result.data.substr(1,result.data.length-2);
        });
    }

});
app.controller('PurchaseListController',function ($scope,myhttp,$stateParams,$filter,$modal,$rootScope,toaster,$state) {
    var sdate='';
    var edate='';
    $scope.query = function(page,filter,urlSdate,urlEdate){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        if(urlEdate&&urlSdate){
            sdate=urlSdate;
            edate=urlEdate;
        }
        myhttp.getData('/index/purchase/purchaseList','GET',
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
                    data.pages.push(i)
                $scope.search_context = filter;
                $scope.data=data;
                $scope.edate=$filter('limitTo')(edate, 10);
                $scope.sdate=$filter('limitTo')(sdate, 10);

            });
    };
    $scope.query($stateParams.page,$stateParams.search,$stateParams.sdate,$stateParams.edate);
    $scope.search=function () {
        if ($scope.sdate && $scope.sdate) {
            if ($scope.sdate > $scope.edate) {
                toaster.pop('info', '结束日期不能早于开始日期');
                $scope.sdate = '';
                $scope.edate = '';
                return false;
            }
            sdate = $filter('date')($scope.sdate, "yyyy-MM-dd 00:00:00");
            edate = $filter('date')($scope.edate, "yyyy-MM-dd 23:59:59");
            $state.go('app.purchase.list', {search: $scope.search_context, sdate: sdate, edate: edate});
        } else {
            $state.go('app.purchase.list', {search: $scope.search_context, sdate: null, edate: null});
        }
    };
    $scope.pageQuery=function (page,search,sdate,edate) {
        $state.go('app.purchase.list', {page:page,search: $scope.search_context, sdate: sdate, edate: edate});
    }
    //删除进货单
    $scope.delete=function (list) {
        toaster.pop('info',"删除中请稍后...",'', 50000);
        var myscope = $rootScope.$new();
        myscope.info=list.supply_name+'的进货单';
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
            scope:myscope
        });
        modalInstance.result.then(function () {
          myhttp.getData('/index/purchase/deletePurchase','POST',{purchase_id:list.purchase_id,info:list.supply_name+':'+list.sum})
              .then(function (res) {
                  toaster.clear();
                   if(res.data.result==1){
                        toaster.pop('success','删除成功！');
                        $scope.query(1,$scope.search_context);
                   }else if(res.data.result==2){
                       toaster.pop('error','已经销售不能删除！');
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
app.controller('PurchaseDetailController',function ($scope,myhttp,$stateParams,toaster,$rootScope,$modal,$state) {
    $scope.supply_name=$stateParams.supply_name;
    $scope.finish=$stateParams.finish;
    myhttp.getData('/index/purchase/purchaseDetail','GET',{purchase_id:$stateParams.purchase_id})
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
            toaster.pop('info',"修改中,请稍等...",'',5000);
            if(isNaN($scope.details[index].sum)||($scope.details[index].sum==0)){toaster.pop('info','请完善修改信息！');return false;}
            var data={};
            data.purchase_detail_id=$scope.details[index].purchase_detail_id;
            data.price=$scope.details[index].price;
            data.number=$scope.details[index].number;
            data.sum=$scope.details[index].sum;
            data.purchase_id=$scope.details[index].purchase_id;
            data.goods_unit_id=$scope.details[index].unit_id;
            myhttp.getData('/index/purchase/purchaseDetailUpdate','POST',data)
                .then(function (res) {
                   // console.log(res.data);
                    toaster.clear();
                    if(res.data.result==1){
                        toaster.pop('success','修改成功！');
                        $scope.details[index].editing=false;
                    }
                });
        }
        $scope.details[index].editing=true;
    };
    $scope.cal=function (index) {
        $scope.details[index].sum=Math.round($scope.details[index].number*$scope.details[index].price);
        var sum=0,num=0;
        angular.forEach($scope.details,function (row) {
            num+=Number(row.number);
            sum+=row.sum;
        });
        $scope.ttl_number=num;
        $scope.ttl_sum=sum;
    };
    $scope.cal_sum=function () {
        var sum=0;
        angular.forEach($scope.details,function (row) {
            sum+=row.sum;
        });
        $scope.ttl_sum=sum;
    };
    $scope.delete=function (detail) {
        var myscope = $rootScope.$new();
        myscope.info=$scope.supply_name+'的进货单';
        var modalInstance = $modal.open({
            templateUrl: 'admin/confirm.html',
            controller: 'ConfirmController',
           // size:'sm',
            scope:myscope
        });
        modalInstance.result.then(function () {
            toaster.pop("info","删除中,请稍等...",'',5000);
            myhttp.getData('/index/purchase/purchaseDetailDelete','POST',
                {purchase_detail_id:detail.purchase_detail_id,purchase_id:detail.purchase_id,goods_unit_id:detail.unit_id})
                .then(function (res) {
                    toaster.clear();
                    if(res.data.result==1){
                        toaster.pop('success','删除成功！');
                        myhttp.getData('/index/purchase/purchaseDetail','GET',{purchase_id:$stateParams.purchase_id})
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
                })
        });

    }
    $scope.myBack=function(){
        $state.go($rootScope.previousState,$rootScope.previousStateParams);
    }
});
app.controller('ScreditController',function ($scope,myhttp,toaster,$stateParams,$modal,$rootScope) {
    $scope.query_ttl=function () {
        myhttp.getData('/index/purchase/ttlCredit','GET')
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
        myhttp.getData('/index/purchase/credit','GET',{page:page})
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
        myscope.supply=list;
        var modalInstance = $modal.open({
            templateUrl: 'admin/purchase/creditDetail.html',
            controller: 'supplyCreditDetailController',
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
});

app.controller('supplyCreditDetailController',function ($scope,myhttp,$modalInstance,$modal,$rootScope) {
      $scope.pay={bank:{}};
      $scope.check=false;
      myhttp.getData('/index/purchase/creditDetail','GET',{supply_id:$scope.supply.supply_id})
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
                if((index<0)||(item.time!=$scope.credits[index].time)) sum += item.ttl;
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
        var purchaseID=[];
        angular.forEach($scope.credits,function(item){
            if(item.selected)purchaseID.push(item.purchase_id);
        });
        $scope.check=true;
        if($scope.pay.paid_sum){
            myhttp.getData("/index/purchase/payment",'POST',{pay:$scope.pay,purchaseID:purchaseID,supply:$scope.supply})
                .then(function (res) {
                    //console.log(res.data);
                    $scope.check=false;
                    $modalInstance.close(res.data.result);
                })

        }
    };
    $scope.carryOver=function () {
        var purchaseID=[];
        var count=0;
        angular.forEach($scope.credits,function(item){
            if(item.selected){purchaseID.push(item.purchase_id);count++;}
        });
        if(count>0){
            $scope.check=true;
            var remark=$modal.open({
                templateUrl: 'admin/carryOverRemark.html',
                controller: 'remarkController'
            });
            remark.result.then(function (remark) {
                myhttp.getData('/index/purchase/carryOver','POST',{purchaseID:purchaseID,summary:remark,supply_id:$scope.supply.supply_id})
                    .then(function (res) {
                        $scope.check=false;
                        $modalInstance.close(res.data.result);
                    });
            });
        }
    }
    $scope.cfPrint=function (purchase_id) {
        var myscope = $rootScope.$new();
        myscope.purchase_id=purchase_id;
        var remark=$modal.open({
            templateUrl: 'admin/purchase/cfPrint.html',
            controller: 'cfPrintController',
            scope:myscope
        });
        
    }
});
app.controller('cfPrintController',function ($modalInstance,$scope,myhttp,$rootScope,$modal) {
    myhttp.getData('/index/purchase/cfPrint','GET',{cf:$scope.purchase_id})
        .then(function (res) {
            $scope.credits = res.data.credit;
        });
    $scope.cfPrint=function (purchase_id) {
        var myscope = $rootScope.$new();
        myscope.purchase_id = purchase_id;
        var remark = $modal.open({
            templateUrl: 'admin/purchase/cfPrint.html',
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
