var logViewer = angular.module('logViewer', [
    'ngRoute',
    'Controllers'
]);

var redirectService = function ($route, $http, $location) {

    var route = $route.current.params.client ? 'api/logs/'+$route.current.params.client :  'api/logs?logs=1';
    $http.get(route)
        .success(function (data) {
            var client, log;
            if(data.slug) {
                client = data.slug;
                log = data.logs[0].slug;
            } else {
                client = data.clients[0].slug;
                log = data.clients[0].logs[0].slug;
            }
            $location.url('logs/'+client+ '/'+log);
        });
};

logViewer.config(['$routeProvider', '$locationProvider',
    function($routeProvider, $locationProvider) {
        $routeProvider.
        when('/logs',  {resolve: { redirect: redirectService }}).
        when('/logs/:client', {resolve: { redirect: redirectService }}).
        when('/logs/:client/:log', {
            templateUrl: 'views/partials/log_file.html',
            controller: 'LogFileController'
        }).
        otherwise({
            redirectTo: '/logs'
        });

        $locationProvider.html5Mode(true);
    }]);

logViewer.filter('nl2br', function() {
    return function (text) {
        if(typeof text != 'string') text = text.toString();
        return text.replace(/\n/g, '<br>');
    }
});

logViewer.filter('urlencode', function() {
    return window.encodeURIComponent;
});

logViewer.filter('shorten', function() {
    return function (value, wordwise, max) {
        if (!value) return '';

        max = parseInt(max, 10);
        if (!max) return value;
        if (value.length <= max) return value;

        value = value.substr(0, max);
        if (wordwise) {
            var lastspace = value.lastIndexOf(' ');
            if (lastspace != -1) {
                value = value.substr(0, lastspace);
            }
        }

        return value + ' ...';
    };
});

logViewer.run(function($rootScope) {
    $rootScope.keys = Object.keys;
});

logViewer.directive('slideable', function () {
        return {
            restrict:'C',
            compile: function (element, attr) {
                // wrap tag
                var contents = element.html();
                element.html('<div class="slideable_content" style="margin:0 !important; padding:0 !important" >' + contents + '</div>');

                return function postLink(scope, element, attrs) {
                    // default properties
                    attrs.duration = (!attrs.duration) ? '200ms' : attrs.duration;
                    attrs.easing = (!attrs.easing) ? 'ease-in-out' : attrs.easing;
                    element.css({
                        'overflow': 'hidden',
                        'height': '0px',
                        'transitionProperty': 'height',
                        'transitionDuration': attrs.duration,
                        'transitionTimingFunction': attrs.easing
                    });
                };
            }
        };
    })
    .directive('slideToggle', function() {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var target, content;

                attrs.expanded = false;

                element.bind('click', function() {
                    if (!target) target = document.querySelector(attrs.slideToggle);
                    if (!content) content = target.querySelector('.slideable_content');

                    if(!attrs.expanded) {
                        content.style.border = '1px solid rgba(0,0,0,0)';
                        var y = content.clientHeight;
                        content.style.border = 0;
                        target.style.height = y + 'px';
                    } else {
                        target.style.height = '0px';
                    }
                    attrs.expanded = !attrs.expanded;
                });
            }
        }
    });
