define(function(require) {
    'use strict';

    var _ = require('underscore');
    var routing = require('routing');
    var error = require('oroui/js/error');

    return {
        /**
         * @param {string} routeName
         * @param {string} [regExpFlags]
         * @returns {RegExp|null} RegExp on which route will be matched else null
         */
        getRouteRegExp: function(routeName, regExpFlags) {
            var route;
            var pattern = '';

            regExpFlags = regExpFlags || 'gi';

            try {
                route = routing.getRoute(routeName);
            } catch (er) {
                error.showErrorInUI(er);
                return null;
            }

            _.each(route.tokens, function(token) {
                if ('variable' === token[0]) {
                    // JS does not support Possessive Quantifiers
                    pattern = '(' + token[2].replace('++', '+').replace(/\//g, '\\$&') + ')' + pattern;
                }

                pattern = token[1].replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&') + pattern;
            });

            return new RegExp(pattern, regExpFlags);
        }
    };
});
