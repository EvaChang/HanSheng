'use strict';

app
  .run(
      function ($rootScope, $state, $stateParams,$localStorage,myhttp) {
          $rootScope.$state = $state;
          $rootScope.$stateParams = $stateParams;        
          $rootScope.$on('$stateChangeSuccess', function(event, to, toParams, from, fromParams) {
            $rootScope.previousState = from;
            $rootScope.previousStateParams = fromParams;
              if ($localStorage.auth == undefined) $state.go('auth.login');
              if($localStorage.auth==null) $state.go('auth.login');
              if(from.url!='/login'){
              myhttp.getData('/index/index','GET').then(function (data) {
                  if(data.data.error==0) $state.go('auth.login');
              });
              }
          });
	}
  )
.config(
      function ($stateProvider,   $urlRouterProvider) {
          $urlRouterProvider
              .otherwise('/auth/loading');
          $stateProvider
              .state('auth',{
                  abstract: true,
                  url:'/auth',
                  template: '<div ui-view class="fade-in"></div>',
                  resolve: {
                      deps: ['$ocLazyLoad',
                        function( $ocLazyLoad ){
                          return $ocLazyLoad.load('admin/auth/ctrl.js');
                      }]
                  }
              })
              .state('auth.loading',{
                  url:'/loading',
                  templateUrl:'admin/auth/loading.html',
              })
              .state('auth.login',{
                  url:'/login',
                  templateUrl:'admin/auth/login.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/auth/jsbn.js').then(
                                  function(){
                                      return $ocLazyLoad.load('admin/js/auth/rsa.js');
                                  }
                              );
                          }]
                  }
              })
              .state('app', {
                  abstract: true,
                  url: '/app',
                  templateUrl: 'admin/app.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad){
                              return $ocLazyLoad.load('toaster').then(
                                  function () {
                                      return $ocLazyLoad.load('ui.select');
                                  }
                              );
                          }]
                  }
              })
              .state('app.dashboard', {
                  url: '/dashboard',
                  templateUrl: 'admin/dashboard.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/dashboard.js');
                          }]
                  },
                  ncyBreadcrumb: {
                    label: '<i class="fa fa-home"></i> 首页'
                  }
              })

              //系统设置
              .state('app.setting', {
                  abstract: true,
                  url: '/setting',
                  template: '<div ui-view class="fade-in"></div>',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad){
                              return $ocLazyLoad.load('js/py.js').then(
                                  function () {
                                      return $ocLazyLoad.load('admin/js/setting/ctrl.js');
                              });
                          }]
                  }
              })
              .state('app.setting.info', {
                  url: '/info',
                  templateUrl: 'admin/setting/info.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '系统设置'
                  }
              })
              .state('app.setting.pwd', {
                  url: '/pwd',
                  templateUrl: 'admin/setting/pwd.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/auth/jsbn.js').then(
                                  function(){
                                      return $ocLazyLoad.load('admin/js/auth/rsa.js');
                                  }
                              );
                          }]
                  },
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '修改密码'
                  }
              })
              .state('app.setting.store', {
                  url: '/store',
                  templateUrl: 'admin/setting/store.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '仓库设置'
                  }
              })
              .state('app.setting.staff', {
                  url: '/staff',
                  templateUrl: 'admin/setting/staff.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '员工管理'
                  }
              })
              .state('apps', {
                  abstract: true,
                  url: '/apps',
                  templateUrl: 'tpl/layout.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad){
                              return $ocLazyLoad.load('toaster').then(function () {
                                  return $ocLazyLoad.load('ui.select').then(function () {
                                      return $ocLazyLoad.load('admin/js/setting/ctrl.js').then(function () {
                                          return $ocLazyLoad.load('js/py.js');
                                      });
                                  });
                              });
                          }]
                  }
              })
              .state('app.setting.unit', {
                  url: '/unit',
                  templateUrl: 'admin/setting/unit.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '单位设置'
                  }
              })
              .state('apps.goods',{
                  url:'/goods',
                  templateUrl:'admin/setting/goods.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '种类产品设置'
                  }
                  }

              )
              //进货管理
              .state('app.purchase', {
              abstract: true,
              url: '/purchase',
              template: '<div ui-view class="fade-in"></div>',
              resolve: {
                  deps: ['$ocLazyLoad',
                      function( $ocLazyLoad ){
                          return $ocLazyLoad.load('admin/js/purchase/ctrl.js');
                      }]
                  }
              })
              .state('app.purchase.supply',{
                  url:'/supply',
                  templateUrl:'admin/purchase/supply.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('js/py.js');
                          }]
                  },
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '供应商管理'
                  }
              })
              .state('app.purchase.purchase',{
              url:'/purchase',
              templateUrl:'admin/purchase/purchase.html',
              resolve: {
                  deps: ['$ocLazyLoad',
                      function( $ocLazyLoad ){
                          return $ocLazyLoad.load('js/py.js');
                      }]
              },
              ncyBreadcrumb: {
                  parent:'app.dashboard',
                  label: '新进货单'
                 }
              })
              .state('app.purchase.list',{
                  url:'/list?page&search&sdate&edate',
                  templateUrl:'admin/purchase/list.html',
                  ncyBreadcrumb: {
                      parent:'app.purchase.purchase',
                      label: '进货列表'
                  }
              })
              .state('app.purchase.detail',{
                  url:'/list/detail/{purchase_id}/{supply_name}/{finish}',
                  templateUrl:'admin/purchase/detail.html',
                  ncyBreadcrumb: {
                      parent:'app.purchase.list',
                      label: '进货明细'
                  }
              })
              .state('app.purchase.scredit',{
                  url:'/scredit',
                  templateUrl:'admin/purchase/scredit.html',
                  ncyBreadcrumb: {
                      parent:'app.purchase.purchase',
                      label: '应付管理'
                  }
              })
              //销售管理
              .state('app.sale', {
                  abstract: true,
                  url: '/sale',
                  template: '<div ui-view class="fade-in"></div>',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/sale/ctrl.js');
                          }]
                  }
              })
              .state('app.sale.member',{
                  url:'/member',
                  templateUrl:'admin/sale/member.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('js/py.js');
                          }]
                  },
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '客户管理'
                  }
              })
              .state('app.sale.index',{
                  url:'/index',
                  templateUrl:'admin/sale/index.html',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('js/py.js');
                          }]
                  },
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '新销售单'
                  }
              })
              .state('app.sale.list',{
                  url:'/list?page&search&sdate&edate',
                  templateUrl:'admin/sale/list.html',
                  ncyBreadcrumb: {
                      parent:'app.sale.index',
                      label: '销售列表'
                  }
              })
              .state('app.sale.detail',{
                  url:'/list/detail/{sale_id}/{member_name}/{finish}',
                  templateUrl:'admin/sale/detail.html',
                  ncyBreadcrumb: {
                      parent:'app.sale.list',
                      label: '销售明细'
                  }
              })
              .state('app.sale.mcredit',{
                  url:'/mcredit',
                  templateUrl:'admin/sale/mcredit.html',
                  ncyBreadcrumb: {
                      parent:'app.sale.index',
                      label: '应收管理'
                  }
              })
              //打印
              .state('print',{
                  abstract: true,
                  url:'/print',
                  template: '<div ui-view class="fade-in"></div>',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/print.js');
                          }]
                  }
              })
              .state('print.saleCredit',{
                  url:'/saleCredit/{member_id}/{member_name}',
                  templateUrl:'admin/sale/printCredit.html',
                  controller:'printSaleCreditController'
              })
              .state('print.sale',{
                  url:'/sale/{sale_id}/{member_name}/{time}',
                  templateUrl:'admin/sale/printSale.html',
                  controller:'printSaleController'
              })
               //财务管理
              .state('app.stock', {
                  abstract: true,
                  url: '/stock',
                  template: '<div ui-view class="fade-in"></div>',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/stock/ctrl.js');
                          }]
                  }
              })
              .state('app.stock.list',{
                  url:'/list',
                  templateUrl:'admin/stock/list.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '库存管理'
                  }
              })
              .state('app.finance', {
                  abstract: true,
                  url: '/finance',
                  template: '<div ui-view class="fade-in"></div>',
                  resolve: {
                      deps: ['$ocLazyLoad',
                          function( $ocLazyLoad ){
                              return $ocLazyLoad.load('admin/js/finance/ctrl.js');
                          }]
                  }
              })
              .state('app.finance.bank',{
                  url:'/bank',
                  templateUrl:'admin/finance/bank.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '银行管理'
                  }
              })
              .state('app.finance.transfer',{
                  url:'/transfer',
                  templateUrl:'admin/finance/transfer.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '银行间转账'
                  }
              })
              .state('app.finance.subject',{
                  url:'/subject',
                  templateUrl:'admin/finance/subject.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '会计科目'
                  }
              })
              .state('app.finance.income',{
                  url:'/income',
                  templateUrl:'admin/finance/income.html',
                  ncyBreadcrumb: {
                      parent:'app.dashboard',
                      label: '收入管理'
                  }
              })
              .state('app.finance.fee',{
                url:'/fee',
                templateUrl:'admin/finance/fee.html',
                ncyBreadcrumb: {
                     parent:'app.dashboard',
                     label: '支出管理'
                 }
              })
              .state('app.finance.bdetail',{
                  url:'/bdetail/{bank_id}/{bank_name}',
                  templateUrl:'admin/finance/bdetail.html',
                  ncyBreadcrumb: {
                      parent:'app.finance.bank',
                      label: '银行明细'
                  }
              })

		}
  );

app.config(function ($httpProvider) {
  $httpProvider.interceptors.push('AuthInterceptor');
});
app.factory('AuthInterceptor', function ($rootScope, $q,$location) {
  return {
    responseError: function (response) {
        if(response.status==401)
        {
            $location.url('/auth/login');
        }
      return $q.reject(response);
    }
  };
});