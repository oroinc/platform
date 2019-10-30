import staticConfig from 'configs.json';

let config;

export default function(moduleId) {
    if (!config) {
        config = combineConfig();
    }
    if (!config[moduleId] && console && console.warn) {
        console.warn('Missing config for module "' + moduleId + '"');
    }
    return config[moduleId] || {};
};

/**
 * Combines config by from statically defined part and
 * config extends defined within page HTML
 *
 * @return {Object}
 */
function combineConfig() {
    const config = mergeConfig({}, staticConfig);
    // makes temp config mep where keys are module names but values are same config options
    const tempConfig = Object.values(config)
        .map(function(moduleConfig) {
            const moduleName = moduleConfig.__moduleName;
            delete moduleConfig.__moduleName;
            return [moduleName, moduleConfig];
        })
        .reduce(function(obj, entry) {
            obj[entry[0]] = entry[1];
            return obj;
        }, {});
    mergeConfig(tempConfig, fetchConfigExtends());
    return config;
}

/**
 * Merges objects recursively,
 * arrays are treated as scalars -- previous values gets overwritten
 *
 * @param {Object} config
 * @param {Object} update
 * @return {Object}
 */
function mergeConfig(config, update) {
    let propName;
    for (propName in update) {
        if (!update.hasOwnProperty(propName)) {
            continue;
        }
        if (update[propName] != null && update[propName].toString() === '[object Object]') {
            if (!config[propName]) {
                config[propName] = {};
            }
            mergeConfig(config[propName], update[propName]);
        } else {
            config[propName] = update[propName];
        }
    }

    return config;
}

/**
 * Fetches config defined in HTML
 *
 * @return {Object}
 */
function fetchConfigExtends() {
    const configExtends = {};
    const selector = 'script[type="application/json"][data-role="config"]';
    const nodes = document.querySelectorAll(selector);

    Array.prototype.forEach.call(nodes, function(node) {
        let configItem;
        try {
            configItem = JSON.parse(node.text);
        } catch (e) {
            console.warn('Ignored invalid inline config extend');
        }

        mergeConfig(configExtends, configItem);
    });

    return configExtends;
}
