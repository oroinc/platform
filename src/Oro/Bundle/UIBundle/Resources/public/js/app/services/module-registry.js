const loadModules = require('oroui/js/app/services/load-modules');
const preloadedModules = {};

module.exports = {
    preload: function(moduleName) {
        return loadModules(moduleName).then(function(module) {
            if (!preloadedModules.hasOwnProperty(moduleName)) {
                preloadedModules[moduleName] = module;
            }
        });
    },

    get: function(moduleName) {
        if (preloadedModules.hasOwnProperty(moduleName)) {
            return preloadedModules[moduleName];
        }

        throw new Error('Module name "' + moduleName + '" has not been loaded yet');
    }
};
