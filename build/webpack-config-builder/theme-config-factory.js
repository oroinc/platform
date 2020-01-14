const merge = require('deepmerge');
const path = require('path');

class ThemeConfigFactory {
    /**
     * @param {ThemesConfigLoader} configLoader
     * @param {DynamicImportsFileWriter} dynamicImportsFileWriter
     * @param {AppModulesFileWriter} appModulesFileWriter
     * @param {ConfigsFileWriter} configsFileWriter
     */
    constructor(configLoader, dynamicImportsFileWriter, appModulesFileWriter, configsFileWriter) {
        this._configLoader = configLoader;
        this._dynamicImportsFileWriter = dynamicImportsFileWriter;
        this._appModulesFileWriter = appModulesFileWriter;
        this._configsFileWriter = configsFileWriter;
    }

    /**
     * @param {string} theme Theme name
     * @param {string} buildPath Path to theme build folder
     * @param {string} configFilepath Path to yaml config file in a bundle
     * @return {Object} List of Webpack entry-points
     */
    create(theme, buildPath, configFilepath) {
        let jsModulesConfig = this._configLoader.loadConfig(theme, configFilepath);

        const {
            map = {},
            shim = {},
            configs,
            aliases = {},
            ['app-modules']: appModules,
            ['dynamic-imports']: dynamicImports
        } = jsModulesConfig;

        if (configs) {
            this._configsFileWriter.write(jsModulesConfig.configs, buildPath);
        }

        if (appModules) {
            this._appModulesFileWriter.write(appModules, buildPath);
        }

        if (dynamicImports) {
            this._dynamicImportsFileWriter.write(dynamicImports, buildPath);
        }

        return { map, shim, aliases };
    }
}

module.exports = ThemeConfigFactory;
