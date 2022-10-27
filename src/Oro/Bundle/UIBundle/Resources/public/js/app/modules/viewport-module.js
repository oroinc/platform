import viewportManager from 'oroui/js/viewport-manager';
import cssVariablesManager from 'oroui/js/css-variables-manager';

cssVariablesManager.onReady(function(cssVariables) {
    viewportManager.initialize({
        cssVariables: cssVariables
    });
});
