define(function(require) {
    'use strict';

    var tools = require('oroui/js/tools');

    return {
        warn: function(message, options) {
            if (tools.debug) {
                if (console && console.error) {
                    // error method is used as it prints stack trace
                    console.error('Warning: ' + message);
                } else {
                    var error = new Error('Warning: ' + message);
                    setTimeout(function() {
                        throw error;
                    }, 0);
                }
            }
        },

        error: function(message, options) {
            throw new Error('Warning: ' + message);
        }
    };
});
