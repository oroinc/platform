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
                let inputs = this._overrideInputs(config.inputs);
                if (config.output === undefined) {
                    throw new Error('"output" for "' + key + '" group in theme "' + theme + '" is not defined');
                }
                entryPoints[config.output.replace(/\.[^/.]+$/, "")] = inputs;
            }
        }
        return entryPoints;
    }

    /**
     * @param {Object} inputs
     * @returns {string[]} List of inputs
     * @private
     */
    _overrideInputs(inputs) {
        let newInputs = [];

        inputs.forEach((input, index) => {
            if (typeof input !== 'string') {
                let oldInput = Object.keys(input)[0];
                let newInput = input[oldInput];
                let oldInputIndex = newInputs.findIndex(element => element === oldInput);

                if (newInput) { // replace input
                    newInputs[oldInputIndex] = newInput;
                } else { // delete input
                    newInputs.splice(oldInputIndex, 1);
                }
                newInputs.splice(index);
            } else {
                newInputs.push(input);
            }
        });

        return newInputs;
    }
}

module.exports = StyleLoader;
