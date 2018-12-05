const path = require('path');
const fs = require('file-system');
const merge = require('deepmerge');
const yaml = require('js-yaml');

class ConfigLoader {
    /**
     * @returns {Array}
     */
    get themes() {
        return this._themes;
    }

    /**
     * @returns {Array}
     */
    get themeNames() {
        return Object.keys(this._themes);
    }

    /**
     * @param {Array} bundles Array of ordered symfony bundle paths
     * @param {string} themesLocation Path inside the bundle, where to find the theme
     * @param {string} themeInfoFileName Yml File name with theme info
     */
    constructor(bundles, themesLocation, themeInfoFileName) {
        this._bundles = bundles;
        this._themes = this._getThemes(themesLocation, themeInfoFileName);
    }

    /**
     * Return list of themes with their parents
     * @param {string} themesLocation
     * @param {string} themeInfoFileName
     * @returns {Object.<string|null>}
     * @private
     */
    _getThemes(themesLocation, themeInfoFileName) {
        let themes = {};
        this._bundles.forEach(bundle => {
            let source = bundle + themesLocation;

            if (!fs.existsSync(source)) return;

            fs.readdirSync(source).forEach(name => {
                let theme = path.resolve(source, name);
                if (!fs.lstatSync(theme).isDirectory()) {
                    return;
                }
                let themeFile = path.resolve(theme, themeInfoFileName);
                if (!fs.existsSync(themeFile)) return;

                if (!(name in themes)) {
                    themes[name] = null;
                }
                let themeInfo = yaml.safeLoad(fs.readFileSync(themeFile, 'utf8'));

                if ('parent' in themeInfo) {
                    themes[name] = themeInfo.parent;
                }
            });
        });

        return themes;
    }

    /**
     * @param {string} filePath Path to the file inside bundle directory where to find the configs
     * @return {Object} Merged Configs loaded from all the bundles Yml files matched by filePath
     */
    loadConfigs(filePath) {
        let configs = {};
        this._bundles.forEach(bundle => {
            let absolutePath = bundle + filePath;
            if (!fs.existsSync(absolutePath)) return;

            let doc = yaml.safeLoad(fs.readFileSync(absolutePath, 'utf8'));
            configs = merge(configs, doc);
        });

        return configs;
    }
}

module.exports = ConfigLoader;
