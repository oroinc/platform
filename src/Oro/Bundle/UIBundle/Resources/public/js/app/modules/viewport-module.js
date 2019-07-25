define(function(require) {
    'use strict';

    var viewportManager = require('oroui/js/viewport-manager');
    var cssVariablesManager  = require('oroui/js/css-variables-manager');
    cssVariablesManager.onReady(function(cssVariables) {
        viewportManager.initialize({
            cssVariables: cssVariables
        });
    });
});
