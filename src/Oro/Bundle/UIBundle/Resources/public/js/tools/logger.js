define(function(require) {
    'use strict';

    var tools = require('oroui/js/tools');
    var _ = require('underscore');
    var console = window.console;

    return {
        /**
         * Shows warning, only in DEV mode
         *
         * ```javascript
         * logger.warn('Value `{{value}}` is not valid', {value: 5});
         * ```
         *
         * @param {String} message - message to warn
         * @param {Object} variables - variables to replace in message text
         */
        warn: function(message, variables) {
            var tpl = _.template(message, {
                interpolate: /\{\{([\s\S]+?)}}/g
            });
            if (tools.debug) {
                if (console && console.error) {
                    // error method is used as it prints stack trace
                    console.error('Warning: ' + tpl(variables || {}));
                } else {
                    var error = new Error('Warning: ' + tpl(variables || {}));
                    setTimeout(function() {
                        throw error;
                    }, 0);
                }
            }
        },

        /**
         * Throws exception with message
         *
         * ```javascript
         * logger.error('Value `{{value}}` is not valid', {value: 5});
         * ```
         *
         * @param {String} message - message for exception to throw
         * @param {Object} variables - variables to replace
         */
        error: function(message, variables) {
            var tpl = _.template(message, {
                interpolate: /{{([\s\S]+?)}}/g
            });
            throw new Error(tpl(variables || {}));
        }
    };
});
