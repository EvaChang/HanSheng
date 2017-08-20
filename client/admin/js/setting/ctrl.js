/**
 * Created by Administrator on 2017/5/13.
 */
'use strict';

app.controller('SettingController', function($scope,$http, $resource,$localStorage,toaster) {
    //alert(mycache.get('goods'));
    $scope.query=function () {
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        var $com = $resource($scope.app.host + "/index/setting/info");
        $com.get(function (data) {
            $scope.info=data;
        });
    };
    $scope.query();
    //修改公司基本信息
    $scope.editInfo=function () {
        toaster.pop('wait','公司信息','修改中...',50000);
        var mydata={'cname':$scope.info.cname,'ctel':$scope.info.ctel,'cadd':$scope.info.cadd};
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        $http({
            method: 'POST',
            url: $scope.app.host + "/index/setting/editInfo",
            data:mydata
        }).then(function(response){
            toaster.clear();
            if(response.data=="1"){
                toaster.pop('success','修改成功！');
            }else{
                toaster.pop("error",'修改错误','请联系管理员。');
            }
        });
    }
});
//种类产品设置
app.controller('GoodsCtrl', function($scope, $resource,$http, $filter,$localStorage,toaster,mycache) {
    $scope.py=function (item) {
        item.goods_sn=makePy(item.goods_name).toString();
    };
    $scope.queryCat=function () {
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        var $com = $resource($scope.app.host + "/cat");
        $com.query(function (data) {
            $scope.cats=data;
        });
    };
    $scope.queryUnit=function () {
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        var $com = $resource($scope.app.host + "/index/setting/unit");
        $com.query(function (data) {
            $scope.units=data;
        });
    };
    $scope.queryGoods=function () {
        toaster.pop('wait','载入中，请稍后...','',50000);
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        var $com = $resource($scope.app.host + "/index/setting/goods");
        $com.query(function (data) {
            toaster.clear();
            $scope.items=data;
            mycache.put('goods',data);
        });
    };

    $scope.queryCat();
    $scope.queryUnit();
    if(mycache.get('goods')==undefined){
         $scope.queryGoods();
    }else {
         $scope.items=mycache.get('goods');
    }

    $scope.filter = '';
    $scope.item='';


    $scope.createCat = function(){
        var cat = {cat_name: '新种类'};
        cat.cat_name = $scope.checkItem(cat, $scope.cats, 'cat_name');
        $scope.cats.push(cat);
    };

    $scope.checkItem = function(obj, arr, key){
        var i=0;
        angular.forEach(arr, function(item) {
            if(item[key].indexOf( obj[key] ) == 0){
                var j = item[key].replace(obj[key], '').trim();
                if(j){
                    i = Math.max(i, parseInt(j)+1);
                }else{
                    i = 1;
                }
            }
        });
        return obj[key] + (i ? ' '+i : '');
    };

    $scope.deleteCat = function(item){
        if(item.cat_id==undefined) {
            $scope.cats.splice($scope.cats.indexOf(item), 1);
        }else{
            toaster.pop('wait','种类删除中...','',50000);
            $http.defaults.headers.common['Authorization'] = $localStorage.auth;
            var $com = $resource($scope.app.host + "/index/setting/catDelete");
            $com.delete({'cat_id':item.cat_id},function (response) {
                toaster.clear();
                if(response.result==1) {
                    toaster.pop('success','种类删除成功！');
                    $scope.queryCat();
                }else if(response.result==2){
                    toaster.pop('warn','该种类下有产品，不能删除！');
                }else
                    toaster.pop('error','种类删除失败！');
            });
        }
    };

    $scope.selectCat = function(item){
        angular.forEach($scope.cats, function(item) {
            item.selected = false;
        });
        $scope.cat = item;
        $scope.cat.selected = true;
        $scope.filter = item.cat_id;
    };

    $scope.selectItem = function(item){
        angular.forEach($scope.items, function(item) {
            item.selected = false;
            item.editing = false;
        });
        $scope.item = item;
        $scope.item.selected = true;
        $scope.item.editing = false;
        $scope.item.is_show=(item.is_show==1);
        $scope.item.promote=(item.promote==1);
        for(var i=0;i<$scope.cats.length;i++){
            if(($scope.cats[i].cat_id==item.cat_id)){
                item.cat_id=$scope.cats[i];
                //return false;
            }
        }
        for(var j=0;j<$scope.units.length;j++){
            //alert(i);
            if(($scope.units[j].unit_id==item.unit_id)){
                item.unit_id=$scope.units[j];
                $scope.units.splice(j,1);
                return false;
            }
        }
    };

    $scope.deleteItem = function(item){
        //$scope.items.splice($scope.items.indexOf(item), 1);
        // $scope.item = $filter('orderBy')($scope.items, 'first')[0];
        //if($scope.item) $scope.item.selected = true;
        item.editing=false;
    };

    $scope.createItem = function(){
        var item = {
            cat_id: $scope.cat?$scope.cat.cat_id:'',
            cat_name:$scope.cat?$scope.cat.cat_name:'',
            goods_id:0,
            is_show:true,
            promote:true
        };
        $scope.items.push(item);
        $scope.selectItem(item);
        $scope.item.editing = true;
    };

    $scope.saveGoods=function (item) {
        if(item.goods_name&&item.goods_sn&&item.cat_id) {
            toaster.pop('wait', '产品保存中...', '', 50000);
            $http.defaults.headers.common['Authorization'] = $localStorage.auth;
            var $com = $resource($scope.app.host + "/index/setting/goodsUpdate");
            $com.save(item, function (response) {
                //alert(JSON.stringify(response));
                toaster.clear();
                if (response.result == 1) {
                    mycache.remove('goods');
                    $scope.queryGoods();
                    item.editing = false;
                    toaster.pop('success', '产品修改成功！');
                } else toaster.pop('error', '产品修改失败！');
            });
        }else{
            toaster.pop('info','请完善产品信息。');
        }

    };
    $scope.editItem = function(item){
        if(item && item.selected){
            item.editing = true;
        }
    };

    
//种类更新与添加
    $scope.doneEditing = function(item){
        for(var i=0;i<$scope.cats.length;i++){
            if(($scope.cats[i].cat_name==item.cat_name)&&($scope.cats.indexOf(item)!=i)){
                toaster.pop('error','添加更新种类名称重复！');
                return false;
            }
        }
        item.editing = false;
        toaster.pop('wait','种类更新中...','',50000);
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        if(item.cat_id==undefined){
             var $com=$resource($scope.app.host+'/catCreate');
             $com.save(item,function (response) {
                 toaster.clear();
                 if(response.result==1) {
                     toaster.pop('success','种类添加成功！');
                     $scope.queryCat();
                 }else toaster.pop('error','添加种类失败！');
             });
        }else {
            var $com = $resource($scope.app.host + "/catUpdate");
            $com.save(item,function (response) {
                toaster.clear();
                if(response.result==1) {
                    toaster.pop('success','种类更新成功！');
                    $scope.queryCat();
                }else toaster.pop('error','种类更新失败！');
            });

        }
    };

});
app.controller('UserController', function($scope,myhttp,Base64,$localStorage,toaster) {
    $scope.check=false;
    $scope.updateUser=function () {
        $scope.check=true;
        if($scope.old_pwd==$scope.new_pwd){
            toaster.pop('info','新密码和旧密码不能相同，请重新输入！');
            $scope.new_pwd=$scope.confirm_password='';
            return false;
        }
       var mydata=Base64.encrypt($localStorage.user + ':' + $scope.old_pwd+':'+$scope.new_pwd);
        toaster.pop('info','密码修改中，请稍等...',5000);
       myhttp.getData('/index/setting/pwdUpdate','POST',{data:mydata}).then(function (res) {
           toaster.clear();
           $scope.check=false;
           $scope.old_pwd=$scope.new_pwd=$scope.confirm_password='';
           if(res.data.result==1){
               toaster.pop('success','密码修改成功！');
           }else toaster.pop('error','旧密码输入有误，请重新输入');
       });
        
    }
});
app.controller('StoreController',function ($scope,myhttp,ngDialog,toaster) {
    myhttp.getData('/index/setting/store','GET').then(function (result) {
        $scope.data=result.data;
        //alert("hello");
    });
    $scope.storeDialog=function (data) {
        ngDialog.open({
            template: 'admin/setting/storeUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
            controller:'StoreUpdateController',
            data:data,
            preCloseCallback: function(value){
                switch(value)
                {
                    case 3:
                        toaster.pop('info','该仓库已经存在，请核对...');
                        break;
                    case 0:
                        toaster.pop('error','仓库更新失败，请刷新后再试!');
                        break;
                    case 1:
                        myhttp.getData('/index/setting/store','GET').then(function (result) {
                            $scope.data=result.data;
                        });
                        break;
                }
            }
        });
    }
});
app.controller('StoreUpdateController',function ($scope,myhttp) {
    var check=true;
    $scope.data=$scope.ngDialogData;
    $scope.updateStore=function () {
        check=false;
        if(typeof( $scope.data.store_id)=="undefined") $scope.data.store_id='' ;
        myhttp.getData('/index/setting/storeUpdate','POST',$scope.data)
            .then(function (res) {
                check=true;
                $scope.closeThisDialog(res.data.result);
            });
    }
    
});
app.controller('StaffController', function($scope,$http, $resource,myhttp,$stateParams,ngDialog,toaster) {
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/setting/staff','GET',{page:page,search:filter})
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
    $scope.staffDialog=function(data) {
        ngDialog.open({
            template: 'admin/setting/staffUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
            controller:'StaffUpdateController',
            data:data,
            preCloseCallback: function(value){
                switch(value)
                {
                    case 3:
                        toaster.pop('info','该员工姓名已经存在，请核对...');
                        break;
                    case 0:
                        toaster.pop('error','员工更新失败，请刷新后再试!');
                        break;
                    case 1:
                        $scope.query($stateParams.page,$stateParams.search);
                        break;
                }
            }
        });
    };
});
app.controller('StaffUpdateController',function ($scope,myhttp) {
    var check=true;
    $scope.data=$scope.ngDialogData;
    $scope.py=function (data) {
        $scope.data.staff_sn=makePy(data).toString();
    };
    $scope.updateStaff=function () {
        check=false;
        if(typeof( $scope.data.staff_id)=="undefined") $scope.data.staff_id='' ;
        myhttp.getData('/index/setting/staffUpdate','POST',$scope.data)
            .then(function (res) {
                check=true;
                $scope.closeThisDialog(res.data.result);
            });
    }
});

app.controller('UnitController', function($scope,$http, $resource,myhttp,$stateParams,ngDialog,toaster) {
    $scope.query = function(page,filter){
        if(!page){
            page=1;
        }else{
            page=parseInt(page);
        }
        if(!filter) filter='';
        myhttp.getData('/index/setting/unit','GET',{page:page,search:filter})
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
    $scope.unitDialog=function(data) {
        ngDialog.open({
            template: 'admin/setting/unitUpdate.html',
            className: 'ngdialog-theme-default' ,
            showClose: false,
            controller:'UnitUpdateController',
            data:data,
            preCloseCallback: function(value){
                switch(value)
                {
                    case 3:
                        toaster.pop('info','该单位已经存在，请核对...');
                        break;
                    case 0:
                        toaster.pop('error','单位更新失败，请刷新后再试!');
                        break;
                    case 1:
                        $scope.query($stateParams.page,$stateParams.search);
                        break;
                }
            }
        });
    };
});
app.controller('UnitUpdateController',function ($scope,myhttp) {
    var check=true;
    $scope.data=$scope.ngDialogData;
    $scope.updateUnit=function () {
        check=false;
        if(typeof( $scope.data.unit_id)=="undefined") $scope.data.unit_id='' ;
        myhttp.getData('/index/setting/unitUpdate','POST',$scope.data)
            .then(function (res) {
                check=true;
                $scope.closeThisDialog(res.data.result);
            });
    }
});


