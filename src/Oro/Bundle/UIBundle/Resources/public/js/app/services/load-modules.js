const _ = require('underscore');
const modules = require('dynamic-imports');

function loadModule(name, ...values) {
    if (!modules[name]) {
        throw new Error(`Module "${name}" is not found in the list of modules used for dynamic-imports`);
    }
    return modules[name]().then(function(module) {
        return values.length === 0 ? module.default : _.pick(module, values);
    });
}

/**
 * Loads dynamic list of modules and execute callback function with passed modules
 *
 * @param {Object.<string, string>|Array.<string>|string} modules
 *  - Object: where keys are formal module names and values are actual
 *  - Array: module names,
 *  - string: single module name
 * @param {function(Object)=} callback
 * @param {Object=} context
 * @return {Promise}
 */
module.exports = function loadModules(modules, callback, context) {
    let requirements;
    let processModules;
    const isModulesArray = _.isArray(modules);

    if (_.isObject(modules) && !isModulesArray) {
        // if modules is an object of {formal_name: module_name}
        requirements = _.values(modules);
        processModules = function(loadedModules) {
            // maps loaded modules into original object
            _.each(modules, _.partial(function(map, value, key) {
                modules[key] = map[value];
            }, _.object(requirements, loadedModules)));
            return modules;
        };
    } else {
        requirements = isModulesArray ? modules : [modules];
        processModules = function(loadedModules) {
            return isModulesArray ? loadedModules : loadedModules[0];
        };
    }

    const promises = requirements.map(function(moduleName) {
        return loadModule(moduleName);
    });

    return Promise.all(promises)
        .then(function(modules) {
            modules = processModules(modules);
            if (callback) {
                callback[isModulesArray ? 'apply': 'call'](context || null, modules);
            }
            // promise can't be resolved a with multiple values
            return modules;
        });
};
