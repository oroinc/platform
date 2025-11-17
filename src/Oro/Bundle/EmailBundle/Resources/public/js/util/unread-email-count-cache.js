import _ from 'underscore';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);
// for some reason module configuration sometimes comes as Object, despite it is defined as Array
// defined {unreadEmailsCount: [{"num":"0","id":0}]}, but comes {unreadEmailsCount: {{"num":"0","id":0}}},
let cache = _.toArray(_.result(config, 'unreadEmailsCount'));
let hasInitState = true;

export default {
    hasInitState: function() {
        return hasInitState;
    },

    set: function(id, count) {
        const newCache = cache.filter(function(item) {
            return item.id !== id;
        });
        newCache.push({
            id: id,
            num: count
        });
        cache = newCache;
    },

    get: function(id) {
        let count;
        const found = _.find(cache, function(item) {
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
