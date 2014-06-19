/*jslint nomen:true*/
/*global define*/
define([
    'chaplin'
], function (Chaplin) {
    'use strict';

    var BaseController, reuses;

    BaseController = Chaplin.Controller.extend({
        /**
         * Handles before-action activity
         *
         * @override
         */
        beforeAction: function (params, route, options) {
            var i;

            if (!route.previous) {
                // if it's first time route, initializes page cache
                this.cache.init(
                    route.path,
                    route.query,
                    Chaplin.mediator.execute('retrieveOption', 'userName') || false
                );
            }

            BaseController.__super__.beforeAction.apply(this, arguments);

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
        _combineRouteUrl: function (route) {
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
             * @param {string} path
             * @param {string} query
             * @param {string} userName
             */
            init: function (path, query, userName) {
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

    reuses = [];

    /**
     * Collects compositions to reuse before controller action
     * @static
     */
    BaseController.addBeforeActionReuse = function () {
        var args = Array.prototype.slice.call(arguments, 0);
        reuses.push(args);
    };

    return BaseController;
});
