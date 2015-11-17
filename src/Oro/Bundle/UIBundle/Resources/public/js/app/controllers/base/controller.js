define(function(require) {
    'use strict';

    var $ = require('jquery');
    var Chaplin = require('chaplin');
    var BaseController;
    var reuses;
    var promiseLoads;

    BaseController = Chaplin.Controller.extend({
        /**
         * Handles before-action activity
         *  - initializes cache (on first route)
         *  - when all compositions are ready to reuse, resolves the deferred to execute action
         *
         * @override
         */
        beforeAction: function(params, route, options) {
            BaseController.__super__.beforeAction.apply(this, arguments);

            var self = this;

            return $.when.apply($, promiseLoads).then(function() {
                var i;
                // if it's first time route
                if (!route.previous) {
                    // initializes page cache
                    self.cache.init(route);
                }
                // compose global instances
                for (i = 0; i < reuses.length; i += 1) {
                    self.reuse.apply(self, reuses[i]);
                }
            });
        },

        /**
         * Combines full URL for the route
         *
         * @param {Object} route
         * @returns {string}
         * @private
         */
        _combineRouteUrl: function(route) {
            var url;
            url = Chaplin.mediator.execute('combineFullUrl', route.path, route.query);
            return url;
        },

        /**
         * Cache accessor
         */
        cache: {
            /**
             * Executes 'init' handler for pageCache manager
             *
             * @param {Object} route
             */
            init: function(route) {
                var path = route.path;
                var query = route.query;
                var userName = Chaplin.mediator.execute('retrieveOption', 'userName') || false;
                Chaplin.mediator.execute({
                    name: 'pageCache:init',
                    silent: true
                }, path, query, userName);
            },

            /**
             * Executes 'get' handler for pageCache manager
             *
             * @param {string=} path
             * @returns {Object|undefined}
             */
            get: function(path) {
                return Chaplin.mediator.execute({
                    name: 'pageCache:get',
                    silent: true
                }, path);
            }
        }
    });

    reuses = [];
    promiseLoads = [
        // add DOM Ready promise to loads promises,
        // in order to prevent route action execution before the page is ready
        $.ready.promise()
    ];

    /**
     * Collects compositions to reuse before controller action
     * @static
     */
    BaseController.addToReuse = function() {
        var args = Array.prototype.slice.call(arguments, 0);
        reuses.push(args);
    };

    /**
     * Wrapper over "require" method.
     *  - loads modules, executes callback and resolves deferred object
     *
     * @param {Array} modules
     * @param {function(...[*])} initCallback
     * @returns {jQuery.Deferred}
     * @static
     */
    BaseController.loadBeforeAction = function(modules, initCallback) {
        var deferredLoad = $.Deferred();
        promiseLoads.push(deferredLoad.promise());
        var callback = function() {
            var args;
            args = Array.prototype.slice.call(arguments, 0);
            initCallback.apply(null, args);
            deferredLoad.resolve();
        };
        require(modules, callback);
    };

    return BaseController;
});
