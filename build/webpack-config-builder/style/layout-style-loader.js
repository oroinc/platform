const path = require('path');

class LayoutStyleLoader {
    /**
     * @param {YamlConfigLoader} configLoader
     * @param {SCSSEntryPointFileWriter} entryPointFileWriter
     */
    constructor(configLoader, entryPointFileWriter) {
        this._configLoader = configLoader;
        this._entryPointFileWriter = entryPointFileWriter;
    }

    /**
     * @param {string} theme Theme name
     * @param {string} buildPath Build path
     * @return {Object} List of Webpack entry-points
     */
    getThemeEntryPoints(theme, buildPath) {
        let entryPoints = {};

        let themeConfig = this._configLoader.loadConfig(theme, '/config/assets.yml');

        for (let key in themeConfig) {
            if (themeConfig.hasOwnProperty(key)) {
                let config = themeConfig[key];
                let inputs = this._overrideInputs(config.inputs);
                inputs = this._sortInputs(inputs);
                if (config.output === undefined) {
                    throw new Error('"output" for "' + key + '" group in theme "' + theme + '" is not defined');
                }
                let entryPointName = config.output.replace(/\.[^/.]+$/, "");
                let filePath = path.join(buildPath, config.output);
                entryPoints[entryPointName] = this._entryPointFileWriter.write('./../../../', inputs, filePath);
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
                newInputs[index] = input;
            }
        });

        return newInputs;
    }

    /**
     * Sort inputs, so first will be settings, then variables, then other files
     * @param {Object} inputs
     * @returns {string[]} List of ordered inputs
     * @private
     */
    _sortInputs(inputs) {
        let settingsInputs = [];
        let variablesInputs = [];
        let restInputs = [];

        inputs.forEach(input => {
            if (input.indexOf('/settings/') > 0) {
                settingsInputs.push(input);
            } else if (input.indexOf('/variables/') > 0) {
                variablesInputs.push(input);
            } else {
                restInputs.push(input);
            }
        });
        return [...settingsInputs, ...variablesInputs, ...restInputs];
    }
}

module.exports = LayoutStyleLoader;
