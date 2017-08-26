'use strict';

/* Controllers */

angular.module('app')
  .controller('AppCtrl', ['$scope', '$translate', '$localStorage', '$window', '$state','$http', 
    function(              $scope,   $translate,   $localStorage,   $window ,$state,$http) {
      // add 'ie' classes to html
      var isIE = !!navigator.userAgent.match(/MSIE/i);
      isIE && angular.element($window.document.body).addClass('ie');
      isSmartDevice( $window ) && angular.element($window.document.body).addClass('smart');
      $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
      $scope.app = {
		host: "/hx/tp5/public/index.php",
        name: 'iHanc',
        version: '1.0.0',
        // for chart colors
        color: {
          primary: '#7266ba',
          info:    '#23b7e5',
          success: '#27c24c',
          warning: '#fad733',
          danger:  '#f05050',
          light:   '#e8eff0',
          dark:    '#3a3f51',
          black:   '#1c2b36'
        },
        settings: {
          themeID: 1,
          navbarHeaderColor: 'bg-black',
          navbarCollapseColor: 'bg-white-only',
          asideColor: 'bg-black',
          headerFixed: true,
          asideFixed: false,
          asideFolded: false,
          asideDock: false,
          container: false
        }
      }

      // save settings to local storage
      if ( angular.isDefined($localStorage.settings) ) {
        $scope.app.settings = $localStorage.settings;
      } else {
        $localStorage.settings = $scope.app.settings;
      }
      $scope.$watch('app.settings', function(){
        if( $scope.app.settings.asideDock  &&  $scope.app.settings.asideFixed ){
          // aside dock and fixed must set the header fixed.
          $scope.app.settings.headerFixed = true;
        }
        // save to local storage
        $localStorage.settings = $scope.app.settings;
      }, true);

      // angular translate
      $scope.lang = { isopen: false };
      $scope.langs = {en:'English', de_DE:'German', it_IT:'Italian'};
      $scope.selectLang = $scope.langs[$translate.proposedLanguage()] || "English";
      $scope.setLang = function(langKey, $event) {
        // set the current lang
        $scope.selectLang = $scope.langs[langKey];
        // You can change the language during runtime
        $translate.use(langKey);
        $scope.lang.isopen = !$scope.lang.isopen;
      };
	  $scope.session_user = $localStorage.user;
	  $scope.logout = function(){
			$localStorage.auth = null;
			$http.defaults.headers.common['Authorization'] = "Basic";
			$state.go("auth.login");
	  }
      function isSmartDevice( $window )
      {
          // Adapted from http://www.detectmobilebrowsers.com
          var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
          // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
          return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
      }

  }]);
/*factory defined by Eva*/
app.factory('mycache',function ($cacheFactory) {
    var myCache={};
    if($cacheFactory.get('myCache')==undefined){
        myCache=$cacheFactory('myCache');
    }else {
        myCache=$cacheFactory.get('myCache');
    }
    return myCache;
});

app.service('myhttp',function ($http,$localStorage) {
    this.getData=function (url,method,params) {
        $http.defaults.headers.common['Authorization'] = $localStorage.auth;
        if(method=='GET'){
            return $http({
                url:"/hx/tp5/public/index.php"+url,
                method:"GET",
                params:params
            })
        }else{
            return $http({
                url:"/hx/tp5/public/index.php"+url,
                method:method,
                data:params
            })
        }
    };
});
app.factory('Base64',function(){
    // var keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
    return {
        encrypt:function (input) {
            // public key notice the '\'
            var pem = '-----BEGIN PUBLIC KEY-----\
                MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCyDR+V/DU0CyYWakhPSjLwWVmC\
                mMqo3uugmVXPitly7BqltGDW0c1PbcQ4Y+O/lukAa3qBvxEGqwhZSQokUCQ/mCHt\
                FfmwlGmhHpSLZKKtPVOCKyZGCW6JdQs2ijKVgmks3jxSQ0ceeTrKU6f4KWL89DYq\
                CTqExJISjSAq5MAarQIDAQAB\
                -----END PUBLIC KEY-----';

            var key = RSA.getPublicKey(pem);
            return RSA.encrypt(input, key);
        }
    };
});
app.directive("dateDirective", function() {
    return {
        templateUrl: 'admin/blocks/date.html',
        scope:{dt:'='},
        controller:'DatepickerDemoCtrl'
    }
});
app.directive('onFinishRender', function ($timeout) {
    return {
        restrict: 'A',
        link: function (scope) {
            if (scope.$last === true) {
                $timeout(function () {
                    scope.$emit('ngRepeatFinished');
                });
            }
        }
    }
});
