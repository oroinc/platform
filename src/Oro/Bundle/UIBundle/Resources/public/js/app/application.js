define([
    'chaplin',
    'oroui/js/mediator',
    './ready-state-tracker'
], function(Chaplin, mediator, readyStateTracker) {
    'use strict';

    var Application;

    Application = Chaplin.Application.extend({
        initialize: function(options) {
            this.options = options || {};
            mediator.setHandler('retrieveOption', this.retrieveOption, this);
            mediator.setHandler('retrievePath', this.retrievePath, this);
            mediator.setHandler('combineFullUrl', this.combineFullUrl, this);
            mediator.setHandler('combineRouteUrl', this.combineRouteUrl, this);

            // stub handlers, should be defined in some modules
            mediator.setHandler('showLoading', function() {});
            mediator.setHandler('hideLoading', function() {});
            mediator.setHandler('redirectTo', function() {});
            mediator.setHandler('refreshPage', function() {});

            Application.__super__.initialize.apply(this, arguments);

            mediator.setHandler('changeRoute', function(route, options) {
                options = options || {};
                options.changeURL = true;
                this.router.changeURL(null, null, route, options);
                mediator.trigger('route:change');
            }, this);

            mediator.once('dispatcher:dispatch', function() {
                readyStateTracker.markReady('app');
            });
        },

        /**
         * Returns application's initialization option by its name
         *
         * @param prop name of property
         * @returns {*}
         */
        retrieveOption: function(prop) {
            return this.options.hasOwnProperty(prop) ? this.options[prop] : void 0;
        },

        /**
         * Removes root prefix and returns meaningful part of path
         *
         * @param {string} path
         * @returns {string}
         */
        retrievePath: function(path) {
            return path.replace(this.router.removeRoot, '').replace(/^\//i, '');
        },

        /**
         *
         * @param {(string|Object)} path
         * @param {string=} query
         * @returns {string}
         */
        combineRouteUrl: function(path, query) {
            var routeUrl;
            // if first argument is a route object (or a current record from content-manager)
            if (typeof path === 'object') {
                query = path.query;
                path = path.path;
            }
            path = (path[0] !== '/' ? '/' : '') + path;
            routeUrl = path + (query ? '?' + query : '');
            return routeUrl;
        },

        /**
         *
         * @param {(string|Object)} path
         * @param {string=} query
         * @returns {string}
         */
        combineFullUrl: function(path, query) {
            // root is always supposed to have trailing slash
            var root = this.options.root || '\/';
            var url = this.combineRouteUrl(path, query);
            var fullUrl = url[0] === '\/' ? root + url.slice(1) : root + url;
            return fullUrl;
        }
    });

    return Application;
});
