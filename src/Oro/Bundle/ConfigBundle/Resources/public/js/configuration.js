define(function() {
    'use strict';

    var defaults = {};
    var values = {};
    var configuration = {
        get: function(key) {
            if (values.hasOwnProperty(key)) {
                return values[key];
            } else {
                return defaults[key];
            }
        },
        set: function(key, value) {
            values[key] = value;
        }
    };

    return configuration;
});
