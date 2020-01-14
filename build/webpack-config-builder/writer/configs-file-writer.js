const path = require('path');
const fs = require('file-system');

class ConfigsFileWriter {
    /**
     * @param {string} publicPath
     */
    constructor(publicPath) {
        this._publicPath = publicPath;
    }

    /**
     * Write app-modules.js file and return file path
     *
     * @param {Array} configs List of configurable modules
     * @param {string} output Output file path
     * @returns {string} JS file path of an output file
     */
    write(configs, output) {
        let buildPath = path.join(output, 'configs.json');
        let filepath = path.resolve(this._publicPath + buildPath);
        fs.writeFileSync(filepath, JSON.stringify(configs, null, 2));
        return filepath;
    }
}

module.exports = ConfigsFileWriter;
