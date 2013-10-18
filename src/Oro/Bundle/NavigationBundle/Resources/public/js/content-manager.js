/* global define */
define(['underscore', 'oro/mediator', 'oro/navigation', 'oro/sync'],
function (_, mediator, Navigation, sync) {
    'use strict';

    var pages = {};

    function defaultCallback(url) {
        var navigation = Navigation.getInstance();
        navigation.showOutdatedMessage(url);
    }

    function onContentExpired(tags) {
        _.each(pages, function(items, url) {
            _.each(items, function(options) {
                if (_.intersection(options.tags, tags).length) {
                    (options.callback || defaultCallback)(url);
                }
            });
        });
    }

    mediator.on('hash_navigation_request:before', function() {
        var navigation = Navigation.getInstance();
        delete pages[navigation.url];
    });

    sync.subscribe('oro/content/expired', onContentExpired);

    return {
        /**
         *
         * @param {Array.<string>} tags
         * @param {?function(string)} callback
         */
        tagContent: function (tags, callback) {
            var navigation = Navigation.getInstance(),
                url = navigation.url,
                options = {};
            if (!navigation.isInAction()) {
                options.tags = _.isArray(tags) ? tags : [tags];
                if (callback) {
                    options.callback = callback;
                }
                (pages[url] = pages[url] || []).push(options);
            }
        }
    };
});
