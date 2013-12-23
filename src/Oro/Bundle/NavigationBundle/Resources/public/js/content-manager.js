/* global define */
define(['underscore', 'oro/sync', 'oro/mediator', 'oro/messenger', 'oro/translator'],
function (_, sync, mediator, messenger, __) {
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
     * @export oro/content-manager
     * @name   oro.contentManager
     * @type {Object}
     */
    var contentManager,

        /**
         * Hash object with relation page URL -> its content
         * @type {Object.<string, Object>}
         */
        pagesCache = {},

        /**
         * Hash object with relation page URL -> its tags collections
         * @type {Object.<string, Array>}
         */
        pagesTags = {},

        /**
         * Information about current page (URL and tags for current page)
         * @type {Object}
         */
        current = {
            tags: [],
            url: null
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
         * Default template for notification message
         * @type {Function}
         */
        defaultNotificationTemplate;

    /**
     * Remove restore params from url
     *
     * @param {string} url
     * @return {string}
     */
    function clearUrl(url) {
        return url.replace(/[\?&]restore=1/g, '');
    }

    /**
     * Keeper and switcher of current URL
     *
     * in "get" call - returns current url
     * in "set" call - stores a new URL and switches tags collector
     *
     * @param {?string} url
     * @returns {string}
     */
    function currentUrl(url) {
        if (!_.isUndefined(url)) {
            current.url = clearUrl(url);
            pagesTags[current.url] = current.tags;
        }
        return current.url;
    }

    /**
     * On URL changes clean up tags collection and set a new URL as current
     *
     * @param {string} newUrl
     */
    function changeUrl(newUrl) {
        var oldUrl = currentUrl();
        if (!pagesCache[oldUrl]) {
            delete pagesTags[oldUrl];
        }
        current.tags = [];
        currentUrl(newUrl);
    }

    /**
     * Default callback on content outdated
     *
     * shows notification of outdated content
     *
     * @param {string} url
     */
    function defaultCallback(url) {
        var page = contentManager.getPage(url),
            title = page ? '<b>' + page.titleShort + '</b>' : 'the';
        if (notifier) {
            notifier.close();
        }
        notifier = messenger.notificationMessage(
            'warning',
            __("navigation.message.content.outdated", {title: title}),
            { template: defaultNotificationTemplate }
        );
    }

    /**
     * Handler of content update message from server
     *
     * @param {String} tags
     */
    function onUpdate(tags) {
        tags = prepareTags(tags);

        _.each(pagesTags, function(items, url) {
            var handler, callbacks = [];
            // collect callbacks for outdated contents
            _.each(items, function(options) {
                if (_.intersection(options.tags, tags).length) {
                    callbacks.push(options.callback || defaultCallback);
                }
            });
            if (!callbacks.length) {
                return false;
            }

            // filter only unique callbacks to protects notification duplication
            callbacks = _.uniq(callbacks);
            if (url === currentUrl()) {
                // current page is outdated - execute all callbacks
                _.each(callbacks, function (callback) {
                    callback(url);
                });

            } else {
                // cached page is outdated - setup page changing handler
                handler = function(obj) {
                    if (url === obj.url) {
                        _.each(callbacks, function (callback) {
                            callback(url);
                        });
                        mediator.off('hash_navigation_request:refresh', handler);
                    }
                };
                mediator.on('hash_navigation_request:refresh', handler);
            }
            mediator.trigger('content-manager:content-outdated', { url: url, isCurrentPage: url === currentUrl() });
        });
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
     * @param {String} tags
     * @return []
     */
    function prepareTags(tags) {
        tags = _.reject(JSON.parse(tags), function (tag) {
            return (tag.username || null) === currentUser;
        });
        return _.pluck(tags, 'tagname');
    }

    // handles page changing
    mediator.on('hash_navigation_request:start', function(navigation) {
        changeUrl(navigation.url);
        if (notifier) {
            notifier.close();
        }
    });

    // subscribes to data update channel
    sync.subscribe('oro/data/update', onUpdate);

    /**
     * Router for hash navigation
     *
     * @export  oro/content-manager
     * @class   oro.contentManager
     */
    contentManager = {
        /**
         * Setups content management component, sets initial URL
         *
         * @param {string} url
         * @param {string} userName
         */
        init: function (url, userName) {
            currentUrl(url);
            currentUser = userName;
        },

        /**
         * Stores content related tags for current page
         *
         * @param {Array.<string>} tags list of tags
         * @param {?function(string)} callback is optional,
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
         * Clear cache data
         *
         * by URL, or if it's not passed - clears all the cache
         *
         * @param {?string} url
         */
        clearCache: function(url) {
            if (!_.isUndefined(url)) {
                url = clearUrl(url);
                delete pagesCache[url];
                delete pagesTags[url];
            } else {
                pagesCache = {};
            }
        },

        /**
         * Add current page to permanent cache
         *
         * @param {string} url
         * @param {Object} page
         */
        addPage: function(url, page) {
            pagesCache[clearUrl(url)] = page;
        },

        /**
         * Fetches cache data for url
         *
         * @param {string} url
         * @return {Object}
         */
        getPage: function(url) {
            return pagesCache[clearUrl(url)] || false;
        }
    };

    return contentManager;
});
