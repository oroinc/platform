const fs = require('file-system');
const path = require('path');

class BundlesLoader {
    /**
     * Returns an array of ordered symfony bundle paths
     *
     * @param {string} cachePath Path to symfony cache folder
     * @param {string} symfonyEnv Symfony environment where to read the cache from (usually it's "dev" or "prod")
     * @returns {string[]}
     */
    static getBundles(cachePath, symfonyEnv) {
        let bundlesPath;
        if (symfonyEnv !== undefined) {
            bundlesPath = path.resolve(cachePath, symfonyEnv, 'bundles.json');
        } else {
            // try to find bundles.json for a prod environment, if it's not warmed up - fallback to dev
            bundlesPath = path.resolve(cachePath, 'prod', 'bundles.json');
            if (!fs.existsSync(bundlesPath)) {
                bundlesPath = path.resolve(cachePath, 'dev', 'bundles.json');
            }
        }
        if (!fs.existsSync(bundlesPath)) {
            throw new Error('Please run "bin/console cache:warmup --env=' + symfonyEnv +
                '" command to warm up symfony cache and generate "' + bundlesPath + '"');
        }

        return JSON.parse(fs.readFileSync(bundlesPath));
    }
}

module.exports = BundlesLoader;
