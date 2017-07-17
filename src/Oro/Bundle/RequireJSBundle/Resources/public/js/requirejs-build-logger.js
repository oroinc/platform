(function(req) {
    'use strict';
    var excludeList;

    var original = req.load;
    req.load = function(context, moduleName, url) {
        if (excludeList !== undefined && excludeList.indexOf(moduleName) === -1) {
            //excludeList already loaded and moduleName not found
            console.error(moduleName + ' not configured');
        }
        return original.call(this, context, moduleName, url);
    };

    define('requirejs-build-logger-exclude-list', function(require) {
        excludeList = require('module').config().excludeList || [];
    });
    req(['requirejs-build-logger-exclude-list']);
})(requirejs);
