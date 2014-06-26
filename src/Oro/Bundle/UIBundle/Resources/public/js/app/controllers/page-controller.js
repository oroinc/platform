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
            PageController.__super__.initialize.apply(this, arguments);
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

            url = this._combineRouteUrl(route);
            this._setNavigationHandlers(url);

            if (!route.previous || options.silent) {
                // - page just loaded from server, does not require update
                // - page was updated locally and url is changed, no request required
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
                this.onPageRequest(this.model, null, {actionArgs: args});
                this.model.set(cacheItem.page, {actionArgs: args});
                this.onPageUpdated(this.model, null, {actionArgs: args});
            } else {
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
         *  - triggers 'page:beforeChange' event with two parameters oldRoute and newRoute
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

            this.publishEvent('page:beforeChange', oldRoute, newRoute, options);
        },

        /**
         * Handles page request
         *  - triggers 'page:request' event
         *
         * @param {Chaplin.Model} model
         * @param {XMLHttpRequest} xhr
         * @param {Object} options
         */
        onPageRequest: function (model, xhr, options) {
            this.publishEvent('page:request', options.actionArgs);
        },

        /**
         * Handles page request done
         *  - triggers 'page:update' event with
         *  - updates page title
         *
         * @param {Chaplin.Model} model
         * @param {Object} options
         */
        onPageLoaded: function (model, options) {
            var attributes;
            attributes = model.getAttributes();
            this.publishEvent('page:update', attributes, options.actionArgs, options.xhr);
            this.adjustTitle(attributes.title);
        },

        /**
         * Handles page synchronization done
         *  - triggers 'page:afterChange' event with
         *
         * @param {Chaplin.Model} model
         * @param {Object} resp
         * @param {Object} options
         */
        onPageUpdated: function (model, resp, options) {
            this.publishEvent('page:afterChange');
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
         * @param {Object} options
         * @private
         */
        _processRedirect: function (data, options) {
            var url, delimiter, parser;
            url = data.url || data.location;
//            $.isActive(true);
            if (data.fullRedirect) {
                delimiter = url.indexOf('?') !== -1 ? '?' : '&';
                location.replace(url + delimiter + '_rand=' + Math.random());
            } else if (data.redirect) {
                parser = document.createElement('a');
                parser.href = url;
                url = parser.pathname + (parser.search || '');
                this.publishEvent('page:redirect');
                utils.redirectTo({url: url}, _.extend(options, {forceStartup: true, force: true}));
            } else {
                utils.redirectTo({url: url}, options);
            }
        },

        /**
         * Register handler for page reload and redirect
         *
         * @param {string} url
         * @private
         */
        _setNavigationHandlers: function (url) {
            Chaplin.mediator.setHandler('redirectTo', this._processRedirect, this);
            Chaplin.mediator.setHandler('refreshPage', function () {
                Chaplin.utils.redirectTo({url: url}, {forceStartup: true, force: true});
                Chaplin.mediator.trigger('page:refreshed');
            });
            Chaplin.mediator.setHandler('afterPageChange', function () {
                Chaplin.mediator.publishEvent('page:afterChange');
            });
        }
    });

    return PageController;
});
