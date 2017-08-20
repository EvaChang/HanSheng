app.controller('LoadingController',function($scope,$resource,$state,$localStorage,$http){
   // alert();
    if (typeof($localStorage.auth) == "undefined") $state.go('auth.login');
    if($localStorage.auth==null) $state.go('auth.login');
    $http.defaults.headers.common['Authorization'] =$localStorage.auth;
    var $com = $resource($scope.app.host + "/index/index/index");
    $com.get(function(data){
	//	$scope.session_user = $localStorage.user = data;
        if(data.error==0) {
            $state.go('auth.login');
        }else {
            $state.go('app.dashboard');
        }
    },function(){
        $state.go('auth.login');
     })
});
app.controller('LoginController',function($scope,$state,$http,$resource,Base64,$localStorage){
    $scope.login = function(){
        $scope.authError = "";
        var authdata = Base64.encrypt($scope.user.username + ':' + $scope.user.password);
        $http.defaults.headers.common['Authorization'] =authdata;
        var $com = $resource($scope.app.host + "/index/index/login");
        $com.get(function(data){
            if(data.error==0) {
                $scope.authError = "用户名密码错误，请重新输入";
                //return;
            }else {
                $localStorage.user = data.name;
                $scope.session_tel =data.tel;
                $localStorage.auth = data.token;
                $state.go('app.dashboard');
            }
        },function(){
            $scope.authError = $scope.app.host + "/index/index/index";
        })
    }
});

