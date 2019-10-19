const merge = require('deepmerge');

class StyleLoader {
    /**
     * @param {YamlConfigLoader} configLoader
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
        let commonConfig = this._configLoader.loadConfig(theme, '/Resources/config/oro/assets.yml');
        let themeConfig = this._configLoader.loadConfig(theme, '/Resources/public/themes/' + theme + '/settings.yml');

        themeConfig = themeConfig.styles;
        for (let key in themeConfig) {
            if (themeConfig.hasOwnProperty(key)) {
                let config = themeConfig[key];
                if (commonConfig[key]) {
                    commonConfig[key] = merge(commonConfig[key], config);
                }
            }
        }

        for (let key in commonConfig) {
            if (commonConfig.hasOwnProperty(key)) {
                let config = commonConfig[key];
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
