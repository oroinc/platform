/*jslint browser:true, eqeq:true, nomen:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'chaplin',
    'orotranslation/js/translator',
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/models/page-model'
], function ($, _, Chaplin, __, BaseController, PageModel) {
    'use strict';

    var document, location, history, console, utils, mediator, PageController;

    document = window.document;
    location = window.location;
    history = window.history;
    console = window.console;
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

            if (!route.previous) {
                // page just loaded from server, does not require update
                // just trigger event 'page:afterChange'
                this.onPageUpdated(this.model, null, {actionArgs: args});
                return;
            }

            if (!this._beforePageLoad(route, params, options)) {
                // prevent page loading, if there's redirect found
                return;
            }
            delete options.redirection;

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

            // dispose all components, in case it's page update with the same controller instance
            // (eg. POST data submitted and page data received instead of redirect)
            this._disposeComponents();

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
            var initialization, self;
            // suppress 'page:afterChange' event, on server redirection
            if (options.redirection) {
                return;
            }

            self = this;

            // init components
            initialization = mediator.execute('layout:init', document.body);
            initialization.done(function (components) {
                // attach created components to controller, to get them disposed together
                _.each(components, function (component) {
                    if (typeof component.dispose === 'function') {
                        self['component-' + component.cid || _.uniqueId('component')] = component;
                    }
                });

                _.defer(function () {
                    self.publishEvent('page:afterChange');
                });
            });
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
            var pathDesc;
            if (error.redirect) {
                pathDesc = {url: error.location};
                _.extend(options.actionArgs.options, _.pick(error, ['redirect', 'fullRedirect']));
                this._processRedirect(pathDesc, options.actionArgs.options);
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

            payload = {stopPageProcessing: false};
            this.publishEvent('page:beforeError', xhr, payload);
            if (payload.stopPageProcessing) {
                history.back();
                return;
            }

            data = this._parseRawData(rawData);
            if (_.isObject(data)) {
                model.set(data, options);
            } else {
                if (mediator.execute('retrieveOption', 'debug')) {
                    document.writeln(rawData);
                    if (console) {
                        console.error('Unexpected content format');
                    }
                } else {
                    mediator.execute('showMessage', 'error', __('Sorry, page was not loaded correctly'));
                }
            }

            this.publishEvent('page:error', model.getAttributes(), options.actionArgs, xhr);
            this.onPageUpdated(model, null, options);
        },

        /**
         * Process redirect response
         *  see documentation for Chaplin.utils.redirectTo() method
         *
         * @param {Object|string} pathDesc
         * @param {Object=} params
         * @param {Object=} options
         * @private
         */
        _processRedirect: function (pathDesc, params, options) {
            var url, parser, pathname, query;
            options = options || {};
            if (typeof pathDesc === 'object' && pathDesc.url != null) {
                options = params || {};
                // fetch from URL only pathname and query
                parser = document.createElement('a');
                parser.href = pathDesc.url;
                pathname = parser.pathname;
                query = parser.search.substr(1);
                pathDesc.url = pathname + (query && ('?' + query));
            }
            if (options.fullRedirect) {
                query = utils.queryParams.parse(query);
                query['_rand'] = Math.random();
                query = utils.queryParams.stringify(query);
                url = pathname + (query && ('?' + query));
                location.replace(url);
            } else if (options.redirect) {
                this.publishEvent('page:redirect');
                _.extend(options, {forceStartup: true, force: true, redirection: true});
                utils.redirectTo(pathDesc, options);
            } else {
                utils.redirectTo.apply(utils, arguments);
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
                var queue;
                mediator.trigger('page:beforeRefresh', (queue = []));
                options = options || {};
                _.defaults(options, {forceStartup: true, force: true});
                $.when.apply($, queue).done(function (customOptions) {
                    _.extend(options, customOptions || {});
                    utils.redirectTo({url: url}, options);
                    mediator.trigger('page:afterRefresh');
                });
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
                // there's nothing to do
                dataObj = rawData;
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
        },

        /**
         * Disposes all attached page components
         *
         * @private
         */
        _disposeComponents: function () {
            _.each(this, function (component, name) {
                if ('component-' === name.substr(0, 10)) {
                    component.dispose();
                    delete this[name];
                }
            }, this);
        }
    });

    return PageController;
});
