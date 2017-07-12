(function() {
    'use strict';

    var original = require.load;
    require.load = function(context, moduleName, url) {
        console.error(moduleName + ' not configured');
        return original.call(this, context, moduleName, url);
    };
})();
