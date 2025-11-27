import loadModules from 'oroui/js/app/services/load-modules';
const preloadedModules = {};

export default {
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
