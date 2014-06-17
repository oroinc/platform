/*jslint define*/
/*global define*/
define([
    'chaplin'
], function (Chaplin) {
    'use strict';

    var Controller, reuses;

    reuses = [];

    Controller = Chaplin.Controller.extend({
        /**
         * Handles before-action activity
         *
         * @override
         */
        beforeAction: function (params, route, options) {
            var i;

            if (!route.previous) {
                // if it's first time route, initializes page cache
                this.cache.init(route.path, Chaplin.mediator.execute('retrieveOption', 'userName') || false);
            }

            Chaplin.Controller.prototype.beforeAction.apply(this, arguments);

            // compose global instances
            for (i = 0; i < reuses.length; i += 1) {
                this.reuse.apply(this, reuses[i]);
            }
        },

        /**
         * Combines full URL for the route
         *
         * @param {Object} route
         * @returns {string}
         * @private
         */
        _combineRoutePath: function (route) {
            var path, basePath;
            basePath = Chaplin.mediator.execute('retrieveOption', 'root');
            path = basePath + route.path + (route.query ? '?' + route.query : '');
            return path;
        },

        /**
         * Cache accessor
         */
        cache: {
            /**
             * Executes 'init' handler for pageCache manager
             *
             * @param {string} path
             * @param {string} userName
             */
            init: function (path, userName) {
                Chaplin.mediator.execute({
                    name: 'pageCache:init',
                    silent: true
                }, path, userName);
            },

            /**
             * Executes 'get' handler for pageCache manager
             *
             * @param {string=} path
             * @returns {Object|undefined}
             */
            get: function (path) {
                return Chaplin.mediator.execute({
                    name: 'pageCache:get',
                    silent: true
                }, path);
            },

            /**
             * Executes 'add' handler for pageCache manager
             */
            add: function () {
                Chaplin.mediator.execute({
                    name: 'pageCache:add',
                    silent: true
                });
            },

            /**
             * Executes 'remove' handler for pageCache manager
             *
             * @param {string=} path
             */
            remove: function (path) {
                Chaplin.mediator.execute({
                    name: 'pageCache:remove',
                    silent: true
                }, path);
            }
        }
    });

    /**
     * Collects compositions to reuse before controller action
     * @static
     */
    Controller.addBeforeActionReuse = function () {
        var args = Array.prototype.slice.call(arguments, 0);
        reuses.push(args);
    };

    return Controller;
});
