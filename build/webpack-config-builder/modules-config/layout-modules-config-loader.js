const path = require('path');
const merge = require('deepmerge');
const ThemesConfigLoader = require('./modules-config-loader');

class LayoutModulesConfigLoader extends ThemesConfigLoader {
    /**
     * {@inheritdoc}
     */
    loadConfig(theme, filePath) {
        let themeConfig = super.loadConfig(theme, path.join('/Resources/views/layouts/', theme, filePath));
        // recursive process parent theme
        let parentTheme = this.themes[theme];
        if (typeof parentTheme === "string") {
            let parentThemeConfig = this.loadConfig(parentTheme, filePath);
            themeConfig = merge(parentThemeConfig, themeConfig);
        }

        return themeConfig;
    }
}

module.exports = LayoutModulesConfigLoader;
