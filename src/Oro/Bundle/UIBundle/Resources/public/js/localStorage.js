define(function (require) {
    'use strict';
    /**
     * Provides clint-side storage storage
     * Uses localStorage if supported, otherwise cookies
     */
    require('jquery.cookie');
    if (localStorage) {
        try {
            // test localStorage
            localStorage.get('dummy-key');
            return localStorage;
        } catch (e) {
            // catch IE protected mode or browsers w/o localStorage support
        }
    }
    
    // use cookies storage instead
    return {
        setItem: function (name, value) {
            // Create cookie, valid across entire site:
            return $.cookie(name, value, {path: '/'});
        },
        getItem: function (name) {
            return $.cookie(name);
        }
    };
});
