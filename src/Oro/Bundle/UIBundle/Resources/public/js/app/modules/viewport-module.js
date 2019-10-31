define(function(require) {
    'use strict';

    const viewportManager = require('oroui/js/viewport-manager');
    const cssVariablesManager = require('oroui/js/css-variables-manager');
    cssVariablesManager.onReady(function(cssVariables) {
        viewportManager.initialize({
            cssVariables: cssVariables
        });
    });
});
