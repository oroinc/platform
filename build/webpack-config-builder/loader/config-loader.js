const path = require('path');
const _loaderUtils = require('loader-utils');

module.exports = function(source) {
    this.cacheable && this.cacheable();
    const { resolver, relativeTo = '' } = _loaderUtils.getOptions(this) || {};

    if (typeof resolver !== 'function') {
        return source;
    }
    const rawConfigs = JSON.parse(source);
    const mappedConfigs = {};

    for (let [moduleName, config] of Object.entries(rawConfigs)) {
        let moduleId = path.relative(relativeTo, resolver(moduleName)).split(path.sep).join('/');
        if (moduleId[0] !== '/' && moduleId.slice(0, 3) !== '../') {
            moduleId = './' + moduleId;
        }
        mappedConfigs[moduleId] = config;
        mappedConfigs[moduleId].__moduleName = moduleName;
    }

    return JSON.stringify(mappedConfigs);
};
