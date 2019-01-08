const merge = require('deepmerge');

class StyleLoader {
    /**
     * @param {ConfigLoader} configLoader
     */
    constructor(configLoader) {
        this._configLoader = configLoader;
    }

    /**
     * @param {string} theme Theme name
     * @return {Object} List of Webpack entry-points
     */
    getThemeEntryPoints(theme) {
        let entryPoints = {};
        let commonConfigs = this._configLoader.loadConfigs('/Resources/config/oro/assets.yml');
        let themeConfigs = this._configLoader.loadConfigs('/Resources/public/themes/' + theme + '/settings.yml');

        themeConfigs = themeConfigs.styles;
        for (let key in themeConfigs) {
            if (themeConfigs.hasOwnProperty(key)) {
                let config = themeConfigs[key];
                if (commonConfigs[key]) {
                    commonConfigs[key] = merge(commonConfigs[key], config);
                }
            }
        }

        for (let key in commonConfigs) {
            if (commonConfigs.hasOwnProperty(key)) {
                let config = commonConfigs[key];
                if (config.output === undefined) {
                    throw new Error('"output" for "' + key + '" group in theme "' + theme + '" is not defined');
                }
                entryPoints[config.output.replace(/\.[^/.]+$/, "")] = config.inputs;
            }
        }
        return entryPoints;
    }
}

module.exports = StyleLoader;
