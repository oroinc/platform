/*jslint nomen:true*/
/*global define*/
define([
    'chaplin'
], function (Chaplin) {
    'use strict';

    var Application;

    Application = Chaplin.Application.extend({
        initialize: function (options) {
            this.options = options || {};
            Chaplin.mediator.setHandler('retrieveOption', this.retrieveOption, this);
            Chaplin.mediator.setHandler('retrievePath', this.retrievePath, this);
            Chaplin.mediator.setHandler('combineFullUrl', this.combineFullUrl, this);
            Chaplin.mediator.setHandler('combineRouteUrl', this.combineRouteUrl, this);

            // stub handlers, should be defined in some modules
            Chaplin.mediator.setHandler('showLoading', function () {});
            Chaplin.mediator.setHandler('hideLoading', function () {});
            Chaplin.mediator.setHandler('redirectTo', function () {});
            Chaplin.mediator.setHandler('refreshPage', function () {});

            Application.__super__.initialize.apply(this, arguments);
        },

        /**
         * Returns application's initialization option by its name
         *
         * @param prop name of property
         * @returns {*}
         */
        retrieveOption: function (prop) {
            return this.options.hasOwnProperty(prop) ? this.options[prop] : void 0;
        },

        /**
         * Removes root prefix and returns meaningful part of path
         *
         * @param {string} path
         * @returns {string}
         */
        retrievePath: function (path) {
            return path.replace(this.router.removeRoot, '');
        },

        /**
         *
         * @param {(string|Object)} path
         * @param {string=} query
         * @returns {string}
         */
        combineRouteUrl: function (path, query) {
            var routeUrl;
            // if first argument is a route object (or a current record from content-manager)
            if (typeof path === 'object') {
                query = path.query;
                path = path.path;
            }
            routeUrl = path + (query ? '?' + query : '');
            return routeUrl;
        },

        /**
         *
         * @param {(string|Object)} path
         * @param {string=} query
         * @returns {string}
         */
        combineFullUrl: function (path, query) {
            var fullUrl, root, url;
            // root is always supposed to have trailing slash
            root = this.options.root || '\/';
            url = this.combineRouteUrl(path, query);
            fullUrl = url[0] === '\/' ? root + url.slice(1) : root + url;
            return fullUrl;
        }
    });

    return Application;
});
