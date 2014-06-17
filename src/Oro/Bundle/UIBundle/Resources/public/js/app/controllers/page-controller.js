/*jslint browser:true, nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/models/page'
], function (_, Chaplin, BaseController, PageModel) {
    'use strict';

    var document, location, utils, PageController;

    document = window.document;
    location = window.location;
    utils = Chaplin.utils;

    PageController = BaseController.extend({

        /**
         * Creates page model
         * @override
         */
        initialize: function () {
            BaseController.prototype.initialize.apply(this, arguments);
            this.model = new PageModel();
        },

        /**
         * Main action point
         *  - does background work before page loading
         *  - fetches page data from server
         *
         * @param {Object} params
         * @param {Object} route
         * @param {Object} options
         */
        index: function (params, route, options) {
            var url, cacheItem, args;

            if (!route.previous) {
                return;
            }

            this._beforePageLoad(route, params, options);

            args = {// collect arguments to reuse in events of page_fetch state change
                params: params,
                route: route,
                options: options
            };
            cacheItem = this.cache.get(route.path);

            if (cacheItem && options.force !== true) {
                options.fromCache = true;
                this.model.set(cacheItem.page, {actionArgs: args});
                this.publishEvent('page_fetch:after', args);
            } else {
                url = this._combineRoutePath(route);
                this.model.fetch({
                    url: url,
                    validate: true,
                    actionArgs: args
                });
            }
        },

        /**
         * Does preparation for page loading
         *  - adds event listeners on page model
         *  - triggers 'page_fetch:before' event with two parameters oldRoute and newRoute
         *
         * @param {Object} route
         * @param {Object} params
         * @param {Object} options
         * @private
         */
        _beforePageLoad: function (route, params, options) {
            var oldRoute, newRoute, page;

            page = this.model;
            oldRoute = route.previous;
            newRoute = _.extend(_.omit(route, ['previous']), {params: params});

            this.listenTo(page, 'request', this.onPageRequest);
            this.listenTo(page, 'change', this.onPageLoaded);
            this.listenTo(page, 'sync', this.onPageUpdated);
            this.listenTo(page, 'invalid', this.onPageInvalid);

            this.publishEvent('page_fetch:before', oldRoute, newRoute, options);
        },

        /**
         * Handles page request
         *  - triggers 'page_fetch:request' event
         *
         * @param {Chaplin.Model} model
         * @param {XMLHttpRequest} xhr
         * @param {Object} options
         */
        onPageRequest: function (model, xhr, options) {
            this.publishEvent('page_fetch:request', options.actionArgs);
        },

        /**
         * Handles page request done
         *  - triggers 'page_fetch:update' event with
         *  - updates page title
         *
         * @param {Chaplin.Model} model
         * @param {Object} options
         */
        onPageLoaded: function (model, options) {
            var attributes;
            attributes = model.getAttributes();
            this.publishEvent('page_fetch:update', attributes, options.actionArgs);
            this.adjustTitle(attributes.title);
        },

        /**
         * Handles page synchronization done
         *  - triggers 'page_fetch:after' event with
         *
         * @param {Chaplin.Model} model
         * @param {Object} resp
         * @param {Object} options
         */
        onPageUpdated: function (model, resp, options) {
            this.publishEvent('page_fetch:after', options.actionArgs);
        },

        /**
         * Handles invalid page load
         *  - process redirect of it was found
         *
         * @param {Chaplin.Model} model
         * @param {*} error
         * @param {Object} options
         */
        onPageInvalid: function (model, error, options) {
            if (error.redirect) {
                this._processRedirect(error);
            }
        },

        /**
         * Process redirect response
         *
         * @param {Object} data
         * @private
         */
        _processRedirect: function (data) {
            var url, delimiter, parser;
            url = data.location;
//            $.isActive(true);
            if (data.fullRedirect) {
                delimiter = url.indexOf('?') !== -1 ? '?' : '&';
                location.replace(url + delimiter + '_rand=' + Math.random());
            } else {
                parser = document.createElement('a');
                parser.href = url;
                url = parser.pathname + (parser.search || '');
                this.publishEvent('page_fetch:redirect');
                utils.redirectTo({url: url}, {forceStartup: true, force: true});
            }
        }
    });

    return PageController;
});
