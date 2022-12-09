const modules = require('dynamic-imports');

function pick(object, keys) {
    return keys.reduce((obj, key) => {
        if (object && object.hasOwnProperty(key)) {
            obj[key] = object[key];
        }
        return obj;
    }, {});
}

function loadModule(name, ...values) {
    if (!modules[name]) {
        throw new Error(`Module "${name}" is not found in the list of modules used for dynamic-imports`);
    }
    return modules[name]().then(module =>
        values.length === 0 ? module.default : pick(module, values));
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
function loadModules(modules, callback, context) {
    let requirements;
    let processModules;
    const isModulesArray = Array.isArray(modules);

    if (isModulesArray) {
        requirements = modules;
        processModules = loadedModules => loadedModules;
    } else if (typeof modules === 'string') {
        requirements = [modules];
        processModules = loadedModules => loadedModules[0];
    } else {
        // if modules is an object of {formalName: moduleName}
        requirements = Object.values(modules);
        processModules = loadedModules => {
            // maps loaded modules into original object
            Object.keys(modules)
                .forEach((formalName, index) => modules[formalName] = loadedModules[index] || modules[formalName]);
            return modules;
        };
    }

    return Promise.all(requirements.map(moduleName => loadModule(moduleName)))
        .then(modules => {
            modules = processModules(modules);
            if (callback) {
                callback[isModulesArray ? 'apply' : 'call'](context || null, modules);
            }
            // promise can't be resolved a with multiple values
            return modules;
        });
}

/**
 * Loads module from object's property
 *
 * @param {Object} obj
 * @param {string} prop name with a module name
 * @return {Promise}
 */
loadModules.fromObjectProp = function(obj, prop) {
    if (typeof obj[prop] !== 'string') {
        return new Promise(resolve => resolve(obj[prop]));
    }
    return loadModules(obj[prop])
        .then(module => obj[prop] = module);
};

module.exports = loadModules;
