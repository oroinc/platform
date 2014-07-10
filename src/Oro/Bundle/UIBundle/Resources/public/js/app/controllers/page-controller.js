/*jslint browser:true, nomen:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    'orotranslation/js/translator',
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/models/page'
], function (_, Chaplin, __, BaseController, PageModel) {
    'use strict';

    var document, location, utils, mediator, PageController;

    document = window.document;
    location = window.location;
    utils = Chaplin.utils;
    mediator = Chaplin.mediator;

    PageController = BaseController.extend({

        /**
         * Creates page model
         * @override
         */
        initialize: function () {
            var page, isInAction;
            PageController.__super__.initialize.apply(this, arguments);

            page = new PageModel();
            this.listenTo(page, 'request', this.onPageRequest);
            this.listenTo(page, 'change', this.onPageLoaded);
            this.listenTo(page, 'sync', this.onPageUpdated);
            this.listenTo(page, 'invalid', this.onPageInvalid);
            this.listenTo(page, 'error', this.onPageError);
            this.model = page;

            isInAction = false;
            this.subscribeEvent('page:beforeChange', function () {
                isInAction = true;
            });
            this.subscribeEvent('page:afterChange', function () {
                isInAction = false;
            });
            mediator.setHandler('isInAction', function () {
                return isInAction;
            });
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
            args = {// collect arguments to reuse in events of page_fetch state change
                params: params,
                route: route,
                options: options
            };

            if (!route.previous || options.silent) {
                // - page just loaded from server, does not require update
                // - page was updated locally and url is changed, no request required

                if (!route.previous) {
                    // if this first time dispatch, trigger event 'page:afterChange'
                    this.onPageUpdated(this.model, null, {actionArgs: args});
                }
                return;
            }

            if (!this._beforePageLoad(route, params, options)) {
                // prevent page loading, if there's redirect found
                return;
            }

            cacheItem = this.cache.get(route.path);

            if (cacheItem && cacheItem.page && route.query === cacheItem.query && options.force !== true) {
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
         *  - handle route change, if it has place to be
         *
         * @param {Object} route
         * @param {Object} params
         * @param {Object} options
         * @returns {boolean}
         * @private
         */
        _beforePageLoad: function (route, params, options) {
            var oldRoute, newRoute, url, opts;
            oldRoute = route.previous;
            newRoute = _.extend(_.omit(route, ['previous']), {params: params});
            this.publishEvent('page:beforeChange', oldRoute, newRoute, options);

            // if route has been changed during 'page:beforeChange' event,
            // redirect to a new URL and stop processing current
            if (route.path !== newRoute.path || route.query !== newRoute.query) {
                url = this._combineRouteUrl(newRoute);
                opts = _.pick(options, ['forceStartup', 'changeURL', 'force', 'silent']);
                opts.replace = true;
                _.defer(function () {
                    mediator.execute('redirectTo', {url: url}, opts);
                });
                return false;
            }
            return true;
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
            //@todo develop approach to postpone 'page:afterChange' event
            // until all inline scripts on a page have not finished changes
            _.delay(_.bind(this.publishEvent, this), 50, 'page:afterChange');
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
         * Handles page loading error
         *  - tries to parse raw data and keep updating page
         *
         * @param {Chaplin.Model} model
         * @param {XMLHttpRequest} xhr
         * @param {Object} options
         */
        onPageError: function (model, xhr, options) {
            var rawData, data, payload;
            rawData = xhr.responseText;
            data = {};

            if (xhr.status === 200 && rawData.indexOf('http') === 0) {
                data = {redirect: true, fullRedirect: true, location: rawData};
                model.set(data, options);
                return;
            }

            // @TODO make common handler for 403 response and might for other responses
            payload = {stopPageProcessing: false};
            this.publishEvent('page:beforeError', xhr, payload);
            if (payload.stopPageProcessing) {
                return;
            }

            data = this._parseRawData(rawData);
            if (_.isObject(data)) {
                model.set(data, options);
            } else {
                if (mediator.execute('retrieveOption', 'debug')) {
                    document.body.innerHTML = rawData;
                } else {
                    mediator.execute('showMessage', 'error', __('Sorry, page was not loaded correctly'));
                }
            }

            this.publishEvent('page:error', model.getAttributes(), options.actionArgs, xhr);
            this.onPageUpdated(model, null, options.actionArgs);
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
            if (data.fullRedirect) {
                delimiter = url.indexOf('?') === -1 ? '?' : '&';
                location.replace(url + delimiter + '_rand=' + Math.random());
            } else if (data.redirect) {
                parser = document.createElement('a');
                parser.href = url;
                url = parser.pathname + (parser.search || '');
                this.publishEvent('page:redirect');
                options = options || {};
                _.extend(options, {forceStartup: true, force: true});
                utils.redirectTo({url: url}, options);
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
            mediator.setHandler('redirectTo', this._processRedirect, this);
            mediator.setHandler('refreshPage', function (options) {
                options = options || {};
                _.defaults(options, {forceStartup: true, force: true});
                utils.redirectTo({url: url}, options);
                mediator.trigger('page:refreshed');
            });
            mediator.setHandler('submitPage', this._submitPage, this);
            //@TODO discuss why is this handler needed
            mediator.setHandler('afterPageChange', function () {
                // fake page:afterChange event trigger
                mediator.trigger('page:afterChange');
            });
        },

        /**
         * Make data more bulletproof.
         *
         * @param {string} rawData
         * @param {number=} prevPos
         * @returns {Object}
         */
        _parseRawData: function (rawData, prevPos) {
            var jsonStartPos, additionalData, dataObj, data;
            if (_.isUndefined(prevPos)) {
                prevPos = -1;
            }
            rawData = rawData.trim();
            jsonStartPos = rawData.indexOf('{', prevPos + 1);
            additionalData = '';
            dataObj = null;
            if (jsonStartPos > 0) {
                additionalData = rawData.substr(0, jsonStartPos);
                data = rawData.substr(jsonStartPos);
                try {
                    dataObj = JSON.parse(data);
                } catch (err) {
                    return this._parseRawData(rawData, jsonStartPos);
                }
            } else if (jsonStartPos === 0) {
                dataObj = JSON.parse(rawData);
            } else {
                throw "Unexpected content format";
            }

            if (additionalData) {
                additionalData = '<div class="alert alert-info fade in top-messages">' +
                    '<a class="close" data-dismiss="alert" href="#">&times;</a>' +
                    '<div class="message">' + additionalData + '</div></div>';
            }

            if (dataObj.content !== undefined) {
                dataObj.content = additionalData + dataObj.content;
            }

            return dataObj;
        },

        /**
         * Performs save call for a model
         * (is used for saving forms, all data should be already packet into options)
         *
         * @param options
         * @private
         */
        _submitPage: function (options) {
            this.publishEvent('page:beforeChange');
            options.actionArgs = options.actionArgs || {};
            _.defaults(options.actionArgs, {params: {}, route: {}, options: {}});
            this.model.save(null, options);
        }
    });

    return PageController;
});
