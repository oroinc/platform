define([
    'underscore',
    'chaplin',
    'jquery',
    'orosync/js/sync',
    'oroui/js/mediator',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/tools'
], function(_, Chaplin, $, sync, mediator, messenger, __, tools) {
    'use strict';

    /**
     * Hash object with relation page URL -> its content
     * @type {Object.<string, Object>}
     */
    const pagesCache = {};

    /**
     * Information about current page (URL and tags for current page)
     * @type {Object}
     * {
     *    tags: {Array},
     *    path: {string},
     *    query: {string},
     *    page: {Object.<string, *>}, // object with page content
     *    state: {Object.<string, *>} // each page component can cache own state
     * }
     */
    let current = {
        // collect tags and state, even if the page is not initialized
        tags: [],
        state: {}
    };

    /**
     * User ID needed to check whenever update person is the same user
     * @type {String}
     */
    let currentUser = null;

    /**
     * Notifier object
     * @type {Object}
     * {
     *     result: {{close: function()}},
     *     title: {string}
     * }
     */
    const notifier = {
        result: null,
        title: null
    };

    /**
     * Pages that has been out dated
     * @type {Object}
     */
    const outdatedPageHandlers = {};

    /**
     * On URL changes clean up tags collection and set a new URL as current
     *
     * @param {string} path
     * @param {string} query
     */
    function changeUrl(path, query) {
        const item = pagesCache[path] || {};

        _.extend(item, {
            path: path,
            query: query,
            tags: []
        });

        // define default page and state
        _.defaults(item, {
            page: null,
            state: {}
        });

        // there's no previous page, then collected tags and state belong to current page
        if (current.path === null || current.path === void 0) {
            _.extend(item.state, current.state);
            item.tags = current.tags;
        }

        current = item;
    }

    /**
     * Notify messenger about new message
     *
     * @param {string} title
     */
    function sendMessage(title) {
        const options = {
            onClose: function() {
                notifier.result.close();
                notifier.result = null;
                notifier.title = null;
            }
        };
        notifier.result = messenger.notificationMessage(
            'warning',
            __('sync.message.content.outdated', {title: title}),
            options
        );
        notifier.title = title;
    }

    /**
     * Default callback on content outdated
     *
     * shows notification of outdated content
     *
     * @param {string} path
     */
    function defaultCallback(path) {
        const data = contentManager.get(path);
        const title = data ? '<b>' + data.page.titleShort + '</b>' : 'the';
        if (notifier.result === null || notifier.title !== title) {
            sendMessage(title);
        }
    }

    /**
     * Page refresh handler, check whenever
     *
     * @param {string} path
     * @param {Array} callbacks
     */
    function refreshHandler(path, callbacks) {
        if (path === current.path) {
            _.each(callbacks, function(callback) {
                callback(path);
            });
            mediator.off('page:update', outdatedPageHandlers[path]);
            delete outdatedPageHandlers[path];
        }
    }

    /**
     * Handler of content update message from server
     * tags come from server in following structure
     * [
     *  { tagname: TAG, username: AUTHOR},
     *  ...
     *  { tagname: TAG2, username: AUTHOR},
     *  ...
     * ]
     *
     * @param {Array} tags
     */
    function onUpdate(tags) {
        const userTags = _.pluck(_.filter(tags, function(tag) {
            return (tag.username || null) === currentUser;
        }), 'tagname');

        const otherTags = _.pluck(_.reject(tags, function(tag) {
            return (tag.username || null) === currentUser;
        }), 'tagname');

        const pages = _.values(pagesCache);
        if (!_.contains(pages, current)) {
            pages.unshift(current);
        }

        _.each(pages, function(page) {
            let handler;
            let callbacks = [];
            const items = page.tags;
            const path = page.path;

            _.each(items, function(options) {
                // remove page from cache silently if current user made changes
                if (_.intersection(options.tags, userTags).length) {
                    contentManager.remove(page.path);
                }

                // collect callbacks for outdated contents
                if (_.intersection(options.tags, otherTags).length) {
                    callbacks.push(options.callback || defaultCallback);
                }
            });

            if (!callbacks.length) {
                return false;
            }

            // filter only unique callbacks to protects notification duplication
            callbacks = _.uniq(callbacks);
            if (path === current.path) {
                // current page is outdated - execute all callbacks
                _.each(callbacks, function(callback) {
                    callback(path);
                });
            } else {
                if (!outdatedPageHandlers[path]) {
                    handler = _.partial(refreshHandler, path, callbacks);
                    outdatedPageHandlers[path] = handler;
                    mediator.on('page:update', handler);
                }
            }
            mediator.trigger('content-manager:content-outdated', {
                path: path,
                isCurrentPage: path === current.path
            });
        });
    }

    // handles page request
    mediator.on('page:request', function(args) {
        const path = args.route.path !== null && args.route.path !== void 0 ? args.route.path : current.path;
        const query = args.route.query !== null && args.route.query !== void 0 ? args.route.query : current.query;
        changeUrl(path, query);
        if (notifier.result) {
            notifier.result.close();
            notifier.result = null;
            notifier.title = null;
        }
    });

    // handles page update
    mediator.on('page:update', function(page, args) {
        current.page = page;
        const options = args.options;
        // if it's forced page reload and page was in a cache, update it
        if (options.cache === true || (options.force === true && contentManager.get())) {
            contentManager.add();
        }
    });

    // subscribes to data update channel
    sync.subscribe('oro/data/update', onUpdate);

    /**
     * Takes url, picks out path and trims root part
     *
     * @param {string} url
     * @returns {*}
     */
    function fetchPath(url) {
        // it's anchor address inside current page
        if (url[0] === '#') {
            url = current.path;
        }
        const _ref = url.split('#')[0].split('?');
        return mediator.execute('retrievePath', _ref[0]);
    }

    /**
     * Content Management
     *
     * component:
     *  - provides API of page content caching;
     *  - stores content tags for pages;
     *  - listing sever messages for content update;
     *  - shows notification for outdated content;
     *
     * @export orosync/js/content-manager
     * @name   orosync.contentManager
     * @type {Object}
     */
    const contentManager = {
        /**
         * Setups content management component, sets initial URL
         *
         * @param {string} path
         * @param {string} query
         * @param {string} userName
         */
        init: function(path, query, userName) {
            changeUrl(path, query);
            currentUser = userName;
        },

        /**
         * Stores content related tags for current page
         *
         * @param {Array.<string>} tags list of tags
         * @param {function(string)=} callback is optional,
         *      handler which will be executed on content by the tags gets outdated
         */
        tagContent: function(tags, callback) {
            const obj = {
                tags: _.isArray(tags) ? tags : [tags]
            };
            if (callback) {
                obj.callback = callback;
            }

            current.tags.push(obj);
            mediator.trigger('content-manager:content-tagged', {current: current.tags, added: obj});
        },

        /**
         * Clear cached data, by default for current url
         *
         * @param {string=} path part of URL
         */
        remove: function(path) {
            if (_.isUndefined(path)) {
                path = current.path;
            } else {
                path = fetchPath(path);
            }
            delete pagesCache[path];

            if (outdatedPageHandlers[path]) {
                mediator.off('page:update', outdatedPageHandlers[path]);
                delete outdatedPageHandlers[path];
            }
        },

        /**
         * Add current page to permanent cache
         */
        add: function() {
            const path = current.path;
            pagesCache[path] = current;
        },

        /**
         * Fetches cache data for url, by default for current url
         *
         * @param {string=} path part of URL
         * @return {Object|boolean}
         */
        get: function(path) {
            path = _.isUndefined(path) ? current.path : fetchPath(path);
            return pagesCache[path] || undefined;
        },

        /**
         * Return information about current page
         *
         * @return {Object}
         */
        getCurrent: function() {
            return current;
        },

        /**
         * Saves state of a page component in a cache
         *
         * @param {string} key
         * @param {*} value
         * @param {string=} hash
         */
        saveState: function(key, value, hash) {
            if (value !== null && value !== void 0) {
                current.state[key] = value;
            } else {
                delete current.state[key];
            }

            if (_.isUndefined(hash)) {
                // hash is not defined, there's nothing else to do
                return;
            }

            this.changeUrlParam(key, hash);
            mediator.trigger('pagestate:change');
        },

        /**
         * Changes url for current page
         *
         * @param {string} url
         * @param {Object} options
         */
        changeUrl: function(url, options) {
            options = options || {};
            const _ref = url.split('?');
            current.path = _ref[0];
            current.query = _ref[1] || '';
            const route = _.pick(current, ['path', 'query']);
            mediator.execute('changeRoute', route, options);
        },

        /**
         * Updates URL parameter for current page
         *
         * @param {string} param
         * @param {string} value
         */
        changeUrlParam: function(param, value) {
            let query = Chaplin.utils.queryParams.parse(current.query);
            if (query[param] === value || (
                (query[param] === null || query[param] === void 0) &&
                (value === null || value === void 0)
            )) {
                // there's nothing to change in query, skip query update and redirect
                return;
            }

            if (value !== null) {
                // if there's new value, update query
                query[param] = value;
            } else {
                // if there's no new value, delete query part
                delete query[param];
            }

            query = Chaplin.utils.queryParams.stringify(query);
            current.query = query;

            const route = _.pick(current, ['path', 'query']);
            mediator.execute('changeRoute', route, {replace: true});
        },

        /**
         * Fetches state of a page component from cached page
         *
         * @param {string} key
         * @return {*}
         */
        fetchState: function(key) {
            return current.state[key];
        },

        /**
         * Check if state's GET parameter (pair key and hash) reflects current URL
         *
         * @param {string} key
         * @param {string} hash
         */
        checkState: function(key, hash) {
            const query = Chaplin.utils.queryParams.parse(current.query);
            return query[key] === hash || query[key] === void 0 && hash === null;
        },

        /**
         * Retrieve meaningful part of path from url and compares it with reference path
         * (assumes that URL contains only path and query)
         *
         * @param {string} url
         * @param {string=} refPath
         * @returns {boolean}
         */
        compareUrl: function(url, refPath) {
            if (refPath === null || refPath === void 0) {
                refPath = current.path;
            }

            return fetchPath(refPath) === fetchPath(url);
        },

        /**
         * Normalizes and then compares given URL`s
         *
         * @param {string} url1
         * @param {string=} url2 In case second URL is not set - current URL is used
         * @param {object=} options
         * @returns {boolean}
         */
        compareNormalizedUrl: function(url1, url2, options) {
            try {
                if (_.isObject(url2)) {
                    options = url2;
                    url2 = undefined;
                }

                url1 = this.normalizeUrl(url1, options);

                if (_.isUndefined(url2)) {
                    url2 = this.currentUrl();
                }

                url2 = this.normalizeUrl(url2, options);

                return url1 === url2;
            } catch (e) {
                // Skip malformed urls if there are any
                if (e instanceof URIError) {
                    if (console && (typeof console.log === 'function')) {
                        console.log(
                            'Exception occurred during urls comparison',
                            {url1: url1, url2: url2, options: options},
                            e
                        );
                    }

                    return false;
                }

                throw e;
            }
        },

        /**
         * Normalizes given url
         *
         * @param {string} urlString
         * @param {object=} options
         * @returns {string}
         */
        normalizeUrl: function(urlString, options) {
            urlString = urlString.trim();

            const hasRelativeProtocol = urlString.indexOf('//') === 0;
            const isRelativeUrl = !hasRelativeProtocol && /^\.*\//.test(urlString);

            // Prepend protocol
            if (!isRelativeUrl) {
                urlString = urlString.replace(/^(?!(?:\w+:)?\/\/)|^\/\//, 'http:');
            }

            const urlObj = document.createElement('a');
            urlObj.href = urlString;

            // Remove auth
            urlObj.username = '';
            urlObj.password = '';

            // Remove duplicate slashes if not preceded by a protocol
            if (urlObj.pathname) {
                urlObj.pathname = urlObj.pathname.replace(/((?!:).|^)\/{2,}/g, function(_, p1) {
                    if (/^(?!\/)/g.test(p1)) {
                        return p1 + '/';
                    }
                    return '/';
                });
            }

            // Decode URI octets
            if (urlObj.pathname) {
                urlObj.pathname = decodeURI(urlObj.pathname);
            }

            urlObj.search = this.normalizeQuery(urlObj.search, options);

            // Remove trailing slash
            urlObj.pathname = urlObj.pathname.replace(/\/$/, '');

            urlString = mediator.execute('combineRouteUrl', urlObj.pathname, urlObj.search.substr(1));

            // Remove ending `/`
            if (urlObj.pathname === '/' && urlObj.hash === '') {
                urlString = urlString.replace(/\/$/, '');
            }

            return urlString;
        },

        /**
         * Normalizes query
         *
         * @param {string} query
         * @param {object=} options
         * @returns {boolean}
         */
        normalizeQuery: function(query, options) {
            options = _.extend({ignoreGetParameters: []}, options || {});

            let queryObj = tools.unpackFromQueryString(query);
            queryObj = _.pick(queryObj, _.difference(_.keys(queryObj), options.ignoreGetParameters));

            if (queryObj['grid']) {
                queryObj['grid'] = _.mapObject(queryObj['grid'], function(state) {
                    const stateUnpacked = tools.unpackFromQueryString(state);
                    return tools.packToQuerySortedString(stateUnpacked);
                });
            }
            return tools.packToQuerySortedString(queryObj);
        },

        /**
         * Combines route URL for current page
         *
         * @returns {script}
         */
        currentUrl: function() {
            const url = mediator.execute('combineRouteUrl', current);
            return url;
        },

        /**
         * Prevents storing current page in cache
         */
        cacheIgnore: function() {
            mediator.once('page:beforeChange', function(oldRoute) {
                contentManager.remove(oldRoute.path);
            });
        }
    };

    return contentManager;
});
