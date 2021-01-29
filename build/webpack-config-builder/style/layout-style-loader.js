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
        const entryPoints = {};

        const themeConfig = this._configLoader.loadConfig(theme, '/config/assets.yml');

        for (const key in themeConfig) {
            if (themeConfig.hasOwnProperty(key)) {
                const config = themeConfig[key];
                let inputs = this._overrideInputs(config.inputs);
                inputs = this._sortInputs(inputs);
                if (config.output === undefined) {
                    throw new Error('"output" for "' + key + '" group in theme "' + theme + '" is not defined');
                }
                const entryPointName = config.output.replace(/\.[^/.]+$/, '');
                const filePath = path.join(buildPath, config.output);
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
        const newInputs = [];

        inputs.forEach((input, index) => {
            if (typeof input !== 'string') {
                const oldInput = Object.keys(input)[0];
                const newInput = input[oldInput];
                const oldInputIndex = newInputs.findIndex(element => element === oldInput);

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
        const settingsInputs = [];
        const variablesInputs = [];
        const restInputs = [];

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
