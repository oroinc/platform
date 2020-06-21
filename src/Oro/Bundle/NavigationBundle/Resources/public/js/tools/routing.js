define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');
    const error = require('oroui/js/error');

    return {
        /**
         * @param {string} routeName
         * @param {string} [regExpFlags]
         * @returns {RegExp|null} RegExp on which route will be matched else null
         */
        getRouteRegExp: function(routeName, regExpFlags) {
            let route;
            let pattern = '';

            regExpFlags = regExpFlags || 'gi';

            try {
                route = routing.getRoute(routeName);
            } catch (er) {
                error.showErrorInUI(er);
                return null;
            }

            _.each(route.tokens, function(token) {
                if ('variable' === token[0]) {
                    // do not add "_format" placeholder to the pattern because it is optional with default value,
                    // as result the format is not added to generated route URL
                    if ('_format' === token[3]) {
                        return;
                    }
                    // JS does not support Possessive Quantifiers
                    pattern = '(' + token[2].replace('++', '+').replace(/\//g, '\\$&') + ')' + pattern;
                }

                pattern = token[1].replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&') + pattern;
            });

            return new RegExp(pattern, regExpFlags);
        }
    };
});
