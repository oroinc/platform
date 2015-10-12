define(function(require) {
    'use strict';

    var _ = require('underscore');
    var module = require('module');
    var cache = _.result(module.config(), 'unreadEmailsCount') || [];
    var hasInitState = true;
    return {
        hasInitState: function() {
            return hasInitState;
        },

        set: function(id, count) {
            var newCache = cache.filter(function(item) {
                return item.id !== id;
            });
            newCache.push({
                id: id,
                num: count
            });
            cache = newCache;
        },

        get: function(id) {
            var count;
            var found = _.find(cache, function(item) {
                return Number(item.id) === Number(id);
            });
            if (found !== void 0) {
                count = found.num;
            } else if (hasInitState) {
                count = 0;
            } else {
                count = void 0;
            }
            return count;
        },

        clear: function() {
            cache = [];
            hasInitState = false;
        }
    };
});
