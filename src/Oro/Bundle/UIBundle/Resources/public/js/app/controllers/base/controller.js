define(function(require) {
    'use strict';

    var BaseController;
    var $ = require('jquery');
    var Chaplin = require('chaplin');
    var beforeActionPromises = [
        // add DOM Ready promise to loads promises,
        // in order to prevent route action execution before the page is ready
        $.ready
    ];

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

            // if it's first time route
            if (!route.previous) {
                // initializes page cache
                this.cache.init(route);
            }

            return $.when.apply($, beforeActionPromises);
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
    }, {
        /**
         * Adds custom promise object in to beforeAction promises collection
         *
         * @param {Promise} promise
         * @static
         */
        addBeforeActionPromise: function(promise) {
            beforeActionPromises.push(promise);
        }
    });

    return BaseController;
});
