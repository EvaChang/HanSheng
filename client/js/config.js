// config

var app =  
angular.module('app')
  .config(
    [        '$controllerProvider', '$compileProvider', '$filterProvider', '$provide',
    function ($controllerProvider,   $compileProvider,   $filterProvider,   $provide) {
        
        // lazy controller, directive and service
        app.controller = $controllerProvider.register;
        app.directive  = $compileProvider.directive;
        app.filter     = $filterProvider.register;
        app.factory    = $provide.factory;
        app.service    = $provide.service;
        app.constant   = $provide.constant;
        app.value      = $provide.value;
    }
  ])
    .config(function ($stateProvider, $urlRouterProvider,$httpProvider) {
        //IE大坑，谨防上当
           if (!$httpProvider.defaults.headers.get) {
               $httpProvider.defaults.headers.get = {};
               $httpProvider.defaults.headers.get['If-Modified-Since'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
               // extra
               $httpProvider.defaults.headers.get['Cache-Control'] = 'no-cache';
               $httpProvider.defaults.headers.get['Pragma'] = 'no-cache';
           }
        })
  .config(['$translateProvider', function($translateProvider){
    // Register a loader for the static files
    // So, the module will search missing translation tables under the specified urls.
    // Those urls are [prefix][langKey][suffix].
    $translateProvider.useStaticFilesLoader({
      prefix: 'l10n/',
      suffix: '.js'
    });
    // Tell the module what language to use by default
    $translateProvider.preferredLanguage('en');
    // Tell the module to store the language in the local storage
    $translateProvider.useLocalStorage();
  }]);
app.config(function($breadcrumbProvider) {
    $breadcrumbProvider.setOptions({
      templateUrl: 'tpl/blocks/breadcrumb.html'
    });
  });
