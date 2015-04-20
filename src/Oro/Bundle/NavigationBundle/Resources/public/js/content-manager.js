/*jslint nomen:true, eqeq:true*/
/*global define*/
define([
    'underscore',
    'chaplin',
    'orosync/js/sync',
    'oroui/js/mediator',
    'oroui/js/messenger',
    'orotranslation/js/translator'
], function (_, Chaplin, sync, mediator, messenger, __) {
    'use strict';

    /**
     * Content Management
     *
     * component:
     *  - provides API of page content caching;
     *  - stores content tags for pages;
     *  - listing sever messages for content update;
     *  - shows notification for outdated content;
     *
     * @export oronavigation/js/content-manager
     * @name   oronavigation.contentManager
     * @type {Object}
     */
    var contentManager,

        /**
         * Hash object with relation page URL -> its content
         * @type {Object.<string, Object>}
         */
        pagesCache = {},

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
        current = {
            // collect tags and state, even if the page is not initialized
            tags: [],
            state: {}
        },

        /**
         * User ID needed to check whenever update person is the same user
         * @type {String}
         */
        currentUser = null,

        /**
         * Notifier object
         * @type {{close: function()}}
         */
        notifier,

        /**
         * Pages that has been out dated
         * @type {Array}
         */
        outdatedPageHandlers = {};

    /**
     * On URL changes clean up tags collection and set a new URL as current
     *
     * @param {string} path
     * @param {string} query
     */
    function changeUrl(path, query) {
        var item;

        item = pagesCache[path] || {};

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
        if (current.path == null) {
            _.extend(item.state, current.state);
            item.tags = current.tags;
        }

        current = item;
    }

    /**
     * Default callback on content outdated
     *
     * shows notification of outdated content
     *
     * @param {string} path
     */
    function defaultCallback(path) {
        var page = contentManager.get(path),
            title = page ? '<b>' + page.titleShort + '</b>' : 'the';
        if (notifier) {
            notifier.close();
        }
        notifier = messenger.notificationMessage(
            'warning',
            __("navigation.message.content.outdated", {title: title})
        );
    }

    /**
     * Tags come from server in following structure
     * [
     *  { tagname: TAG, username: AUTHOR},
     *  ...
     *  { tagname: TAG2, username: AUTHOR},
     *  ...
     * ]
     *
     * @param {string} tags
     * @return []
     */
    function prepareTags(tags) {
        tags = _.reject(JSON.parse(tags), function (tag) {
            return (tag.username || null) === currentUser;
        });
        return _.pluck(tags, 'tagname');
    }

    /**
     * Page refresh handler, check whenever
     *
     * @param {string} path
     * @param {Array} callbacks
     */
    function refreshHandler(path, callbacks) {
        if (path === current.path) {
            _.each(callbacks, function (callback) {
                callback(path);
            });
        }
    }

    /**
     * Handler of content update message from server
     *
     * @param {string} tags
     */
    function onUpdate(tags) {
        var pages;
        tags = prepareTags(tags);

        pages = [current].concat(_.values(pagesCache));

        _.each(pages, function (page) {
            var handler, items, path, callbacks;

            callbacks = [];
            items = page.tags;
            path = page.path;

            // collect callbacks for outdated contents
            _.each(items, function (options) {
                if (_.intersection(options.tags, tags).length) {
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
                _.each(callbacks, function (callback) {
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
    mediator.on('page:request', function (args) {
        var path, query;
        path = args.route.path != null ? args.route.path : current.path;
        query = args.route.query != null ? args.route.query : current.query;
        changeUrl(path, query);
        if (notifier) {
            notifier.close();
        }
    });

    // handles page update
    mediator.on('page:update', function (page, args) {
        var options;
        current.page = page;
        options = args.options;
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
     * @param url
     * @returns {*}
     */
    function fetchPath(url) {
        var _ref;
        _ref = url.split('?');
        return mediator.execute('retrievePath', _ref[0]);
    }

    contentManager = {
        /**
         * Setups content management component, sets initial URL
         *
         * @param {string} path
         * @param {string} query
         * @param {string} userName
         */
        init: function (path, query, userName) {
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
        tagContent: function (tags, callback) {
            var obj = {
                tags: _.isArray(tags) ? tags : [tags]
            };
            if (callback) {
                obj.callback = callback;
            }

            current.tags.push(obj);
            mediator.trigger('content-manager:content-tagged', { current: current.tags, added: obj });
        },

        /**
         * Clear cached data, by default for current url
         *
         * @param {string=} path part of URL
         */
        remove: function (path) {
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
        add: function () {
            var path;
            if (current.path[0] !== '/') {
                current.path = '/' + current.path;
            }
            path = current.path;
            pagesCache[path] = current;
        },

        /**
         * Fetches cache data for url, by default for current url
         *
         * @param {string=} path part of URL
         * @return {Object|boolean}
         */
        get: function (path) {
            path = _.isUndefined(path) ? current.path : fetchPath(path);
            return pagesCache[path] || undefined;
        },

        /**
         * Saves state of a page component in a cache
         *
         * @param {string} key
         * @param {*} value
         * @param {string=} hash
         */
        saveState: function (key, value, hash) {
            if (value !== null) {
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
        changeUrl: function (url, options) {
            var route, _ref;
            options = options || {};
            _ref = url.split('?');
            current.path = _ref[0];
            current.query = _ref[1] || '';
            route = _.pick(current, ['path', 'query']);
            mediator.execute('changeRoute', route, options);
        },

        /**
         * Updates URL parameter for current page
         *
         * @param {string} param
         * @param {string} value
         */
        changeUrlParam: function (param, value) {
            var route, query;

            query = Chaplin.utils.queryParams.parse(current.query);
            if (query[param] === value || (query[param] == null && value == null)) {
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

            route = _.pick(current, ['path', 'query']);
            mediator.execute('changeRoute', route, {replace: true});
        },

        /**
         * Fetches state of a page component from cached page
         *
         * @param {string} key
         * @return {*}
         */
        fetchState: function (key) {
            return current.state[key];
        },

        /**
         * Check if state's GET parameter (pair key and hash) reflects current URL
         *
         * @param {string} key
         * @param {string} hash
         */
        checkState: function (key, hash) {
            var query;
            query = Chaplin.utils.queryParams.parse(current.query);
            return query[key] == hash;
        },

        /**
         * Retrieve meaningful part of path from url and compares it with reference path
         * (assumes that URL contains only path and query)
         *
         * @param {string} url
         * @param {string} refPath
         * @returns {boolean}
         */
        compareUrl: function (url, refPath) {
            if (refPath == null) {
                refPath = current.path;
            }

            return fetchPath(refPath) === fetchPath(url);
        },

        /**
         * Combines route URL for current page
         *
         * @returns {script}
         */
        currentUrl: function () {
            var url;
            url = mediator.execute('combineRouteUrl', current);
            return url;
        },

        /**
         * Prevents storing current page in cache
         */
        cacheIgnore: function () {
            mediator.once('page:beforeChange', function (oldRoute) {
                contentManager.remove(oldRoute.path);
            });
        }
    };

    return contentManager;
});
