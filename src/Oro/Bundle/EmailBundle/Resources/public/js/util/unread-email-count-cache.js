define(function(require) {
    'use strict';

    var _ = require('underscore');
    var module = require('module');
    // for some reason module configuration sometimes comes as Object, despite it is defined as Array
    // defined {unreadEmailsCount: [{"num":"0","id":0}]}, but comes {unreadEmailsCount: {{"num":"0","id":0}}},
    var cache = _.toArray(_.result(module.config(), 'unreadEmailsCount'));
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
