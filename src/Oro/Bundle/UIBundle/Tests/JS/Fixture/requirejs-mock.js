define(function() {
    'use strict';

    const modules = {
        'js/module-a': {moduleName: 'a'},
        'js/module-b': {moduleName: 'b'},
        'js/module-c': {moduleName: 'c'}
    };

    return function(moduleNames, loadHandler, errorHandler) {
        setTimeout(function() {
            try {
                const loadedModules = moduleNames.map(function(name) {
                    if (!modules[name]) {
                        throw new Error('Module not found');
                    }
                    return modules[name];
                });
                loadHandler(...loadedModules);
            } catch (e) {
                errorHandler(e);
            }
        });
    };
});
