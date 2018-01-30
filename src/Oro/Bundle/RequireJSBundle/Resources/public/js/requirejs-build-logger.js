(function(req) {
    'use strict';
    var excludeList;
    var console = window.console;

    var original = req.load;
    req.load = function(context, moduleName, url) {
        if (!context.config.paths[moduleName] && (!excludeList || excludeList.indexOf(moduleName) === -1)) {
            // excludeList already loaded and moduleName not found
            var message = '"' + moduleName + '" not found in built JS file. ' +
                'Add this file into RequireJS "paths" config';
            console.error(message);
        }
        return original.call(this, context, moduleName, url);
    };

    define('requirejs-build-logger-exclude-list', function(require) {
        excludeList = require('module').config().excludeList || [];
    });
    req(['requirejs-build-logger-exclude-list']);
})(requirejs);
