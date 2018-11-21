var controllers = angular.module('Controllers', ['ngRoute']);

Object.prototype.getKeyByValue = function( value ) {
    for (var prop in this) {
        if (this.hasOwnProperty(prop)) {
            if (this[prop] === value)
                return prop;
        }
    }
};

controllers.controller('LogFileController', ['$scope', '$http', '$routeParams', '$timeout',
    function ($scope, $http, $routeParams) {
        $scope.route = $routeParams;
        $scope.busy = false;
        $scope.$parent.busySearch = false;
        $scope.context = [];
        $scope.$parent.resetFilters();
        $scope.$parent.route = $routeParams;
        $scope.$parent.isFiltered = false;
        $scope.filterTextTimeout = null;
        $scope.init = true;
        $scope.$parent.searchInputWide = false;

        $scope.$parent.levels = {
            100: 'debug',
            200: 'info',
            250: 'notice',
            300: 'warning',
            400: 'error',
            500: 'critical',
            550: 'alert',
            600: 'emergency'
        };
        $scope.$parent.levelIcons = {
            100: 'bug',
            200: 'info-circle',
            250: 'file-text',
            300: 'warning',
            400: 'times-circle',
            500: 'fire',
            550: 'bell',
            600: 'flash'
        };

        $scope.success = function(message) { $scope.$parent.success(message); };
        $scope.info    = function(message) { $scope.$parent.info(message); };
        $scope.error   = function(message) { $scope.$parent.error(message); };

        $scope.clipboardSuccess = function(e) {
            e.clearSelection();
            showTooltip(e.trigger, 'Copied!', 'success');
        };

        $scope.clipboardError = function(e) {
            showTooltip(e.trigger, fallbackMessage(), 'error');
            console.error(e);
        };

        $scope.getLog = function (client, log) {
            $scope.busy = true;
            $http.get('api/logs/'+client+'/'+log, { params: $scope.$parent.filter })
                .then(function successCallback(response) {
                    $scope.$parent.currentLog = response.data;
                    $scope.$parent.busySearch = false;
                    $scope.scrollTop();
                    initTooltips();
                    $scope.busy = false;
                }, function errorCallback() {
                    $scope.error('Could not load log lines');
                    $scope.busy = false;
                    $scope.$parent.busySearch = false;
                });
        };

        $scope.getMore = function () {
            $scope.busy = true;
            $http.get($scope.$parent.currentLog.next_page_url)
                .then(function successCallback(response) {
                    $scope.$parent.currentLog.lines.push.apply($scope.$parent.currentLog.lines, response.data.lines);
                    $scope.$parent.currentLog.next_page_url = response.data.next_page_url;
                    $scope.busy = false;
                    //initTooltips();
                }, function errorCallback() {
                    $scope.error('Could not more log lines');
                    $scope.busy = false;
                });
        };

        $scope.getConfig = function () {
            $http.get('api/config')
                .then(function successCallback(response) {
                    $scope.config = response.data;
                }, function errorCallback() {
                    $scope.error('Could not load config');
                });
        };

        $scope.formatDate = function(date) {
            var a = date.split(/[^0-9]/);
            return new Date (a[0],a[1]-1,a[2],a[3],a[4],a[5] );
        };

        $scope.$parent.getLevelNumber = function(level) {
            return $scope.$parent.levels.getKeyByValue(level.toLowerCase());
        };

        $scope.$parent.getLevelIcon = function(level) {
            if (level in $scope.$parent.levelIcons) return $scope.$parent.levelIcons[level];
            return null;
        };

        $scope.toggleContext = function(id) {
            $scope.$parent.currentLog.lines[id].contextToggle = !($scope.$parent.currentLog.lines[id].contextToggle);
            console.log(id+": "+$scope.$parent.currentLog.lines[id].contextToggle);
        };

        $scope.scrollTop = function () {
            document.getElementById("logtop").scrollIntoView(true);
        };

        $scope.resetFilters = function () {
            $scope.$parent.isFiltered = false;
            $scope.$parent.filter = {
                text: null,
                logger: null,
                level: 100
            };
        };

        $scope.getConfig();
        $scope.getLog($scope.route.client, $scope.route.log);
        $scope.$watchGroup(['$parent.filter.logger', '$parent.filter.level', '$parent.filter.text'], function(nv, ov) {
            if($scope.init) {
                $scope.init = false;
            } else {
                if($scope.filterTextTimeout) { clearTimeout($scope.filterTextTimeout); }
                $scope.filterTextTimeout = setTimeout(function() {
                    if($scope.$parent.filter.text != "" || $scope.$parent.filter.logger != null || $scope.$parent.filter.level > 100) {
                        $scope.$parent.isFiltered = true;
                    }
                    $scope.$parent.busySearch = true;
                    $scope.getLog($scope.$parent.route.client, $scope.$parent.route.log);
                },300);
            }
        });

        $scope.$on('$locationChangeStart', function(event) {
            $scope.$parent.currentLog = null;
            $scope.resetFilters();
        });
    }]);

controllers.controller('MainController', ['$scope', '$http',
    function ($scope, $http) {
        $scope.alerts = [];

        $scope.clearCache = function () {
            $http.get('api/cache/clear')
                .then(function successCallback() {
                    $scope.success('The cache has successfully been cleared.');
                }, function error(response) {
                    console.error(response);
                });
        };

        $scope.resetFilters = function() {
            $scope.filter = {
                text: null,
                logger: null,
                level: 100
            };
        };

        $http.get('api/logs?logs=1')
            .then(function successCallback(response) {
                $scope.clients = response.data.clients;
            }, function errorCallback() {
                $scope.error('Could not load log files.');
            });

        $scope.alert = function(type, message) {
            $scope.alerts.push({
                type: type,
                message: message
            });
        };

        $scope.success = function(message) { $scope.alert('success', message); };
        $scope.info    = function(message) { $scope.alert('info', message); };
        $scope.error   = function(message) { $scope.alert('error', message); };
    }]);
