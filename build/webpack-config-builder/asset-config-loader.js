const fs = require('file-system');
const path = require('path');

class AssetConfigLoader {
    /**
     * Returns an object that represents asset configuration
     *
     * @param {string} cachePath Path to symfony cache folder
     * @param {string} symfonyEnv Symfony environment where to read the cache from (usually it's "dev" or "prod")
     * @returns {Object}
     */
    static getConfig(cachePath, symfonyEnv) {
        let configPath;
        if (symfonyEnv !== undefined) {
            configPath = path.resolve(cachePath, symfonyEnv, 'asset-config.json');
        } else {
            // try to find config.json for a prod environment, if it's not warmed up - fallback to dev
            configPath = path.resolve(cachePath, 'prod', 'asset-config.json');
            if (!fs.existsSync(configPath)) {
                configPath = path.resolve(cachePath, 'dev', 'asset-config.json');
            }
        }
        if (!fs.existsSync(configPath)) {
            throw new Error('Please run "bin/console cache:warmup --env=' + symfonyEnv +
                '" command to warm up symfony cache and generate "' + configPath + '"');
        }

        return JSON.parse(fs.readFileSync(configPath));
    }
}

module.exports = AssetConfigLoader;
