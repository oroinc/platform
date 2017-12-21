define(function() {
    'use strict';

    var modules = {
        'js/module-a': {moduleName: 'a'},
        'js/module-b': {moduleName: 'b'},
        'js/module-c': {moduleName: 'c'}
    };

    return function(moduleNames, loadHandler, errorHandler) {
        setTimeout(function() {
            try {
                var loadedModules = moduleNames.map(function(name) {
                    if (!modules[name]) {
                        throw new Error('Module not found');
                    }
                    return modules[name];
                });
                loadHandler.apply(null, loadedModules);
            } catch (e) {
                errorHandler(e);
            }
        });
    };
});
