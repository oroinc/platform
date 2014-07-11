/*jslint nomen:true*/
/*global define, require*/
define([
    'jquery',
    'chaplin'
], function ($, Chaplin) {
    'use strict';

    var BaseController, reuses, promises;

    BaseController = Chaplin.Controller.extend({
        /**
         * Handles before-action activity
         *  - initializes cache (on first route)
         *  - when all compositions are ready to reuse, resolves the deferred to execute action
         *
         * @override
         */
        beforeAction: function (params, route, options) {
            var i, deferred, self;

            if (!route.previous) {
                // if it's first time route, initializes page cache
                this.cache.init(
                    route.path,
                    route.query,
                    Chaplin.mediator.execute('retrieveOption', 'userName') || false
                );
            }

            BaseController.__super__.beforeAction.apply(this, arguments);

            deferred = $.Deferred();
            self = this;

            $.when.apply($, promises).then(function () {
                // compose global instances
                for (i = 0; i < reuses.length; i += 1) {
                    self.reuse.apply(self, reuses[i]);
                }
                // all compositions are ready, allows to execute action
                deferred.resolve();
            });

            return deferred;
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
            }
        }
    });

    reuses = [];
    promises = [];

    /**
     * Collects compositions to reuse before controller action
     * @static
     */
    BaseController.addToReuse = function () {
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
    BaseController.loadBeforeAction = function (modules, initCallback) {
        var deferred, callback;
        deferred = $.Deferred();
        promises.push(deferred);
        callback = function () {
            var args;
            args = Array.prototype.slice.call(arguments, 0);
            initCallback.apply(null, args);
            deferred.resolve();
        };
        require(modules, callback);
    };

    return BaseController;
});
