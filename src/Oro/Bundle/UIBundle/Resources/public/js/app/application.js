define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Chaplin = require('chaplin');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const BaseController = require('oroui/js/app/controllers/base/controller');
    const PageLayoutView = require('oroui/js/app/views/page-layout-view');

    const Application = Chaplin.Application.extend({
        initialize: function(options) {
            this.options = options || {};

            if (this.options.debug !== undefined) {
                tools.debug = this.options.debug;
            }

            mediator.setHandler('retrieveOption', this.retrieveOption, this);
            mediator.setHandler('retrievePath', this.retrievePath, this);
            mediator.setHandler('combineFullUrl', this.combineFullUrl, this);
            mediator.setHandler('combineRouteUrl', this.combineRouteUrl, this);

            // stub handlers, should be defined in some modules
            mediator.setHandler('showLoading', function() {});
            mediator.setHandler('hideLoading', function() {});
            mediator.setHandler('redirectTo', function() {});
            mediator.setHandler('refreshPage', function() {});

            Application.__super__.initialize.call(this, options);

            mediator.setHandler('changeRoute', function(route, options) {
                options = options || {};
                options.changeURL = true;
                this.router.changeURL(null, null, route, options);
                mediator.trigger('route:change');
            }, this);
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
            // if first argument is a route object (or a current record from content-manager)
            if (typeof path === 'object') {
                query = path.query;
                path = path.path;
            }
            path = (path[0] !== '/' ? '/' : '') + path;
            const routeUrl = path + (query ? '?' + query : '');
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
            const root = this.options.root || '\/';
            const url = this.combineRouteUrl(path, query);
            const fullUrl = url[0] === '\/' ? root + url.slice(1) : root + url;
            return fullUrl;
        },

        /**
         * @inheritdoc
         * Standard Chaplin.Layout replaced with custom page layout view
         */
        initLayout: function(options) {
            options = _.defaults({}, options);
            if (!options.title) {
                options.title = this.title;
            }
            options.autoRender = true;

            this.layout = new PageLayoutView(options);
            BaseController.addBeforeActionPromise(this.layout.getDeferredRenderPromise());

            return this.layout;
        }
    });

    return Application;
});
