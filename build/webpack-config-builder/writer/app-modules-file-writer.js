const path = require('path');
const fs = require('fs');

class AppModulesFileWriter {
    /**
     * @param {string} publicPath
     */
    constructor(publicPath) {
        this._publicPath = publicPath;
    }

    /**
     * Write app-modules.js file and return file path
     *
     * @param {Array} appModules List of app modules
     * @param {string} output Output file path
     * @returns {string} JS file path of an output file
     */
    write(appModules, output) {
        const buildPath = path.join(output, 'app-modules.js');
        const requireLines = appModules.map(appModule => `  require('${appModule}')`).join(',\n');
        const content = `export default [\n${requireLines}\n];\n`;
        const filepath = path.resolve(this._publicPath + buildPath);
        fs.mkdirSync(path.dirname(filepath), {recursive: true});
        fs.writeFileSync(filepath, content);

        return filepath;
    }
}

module.exports = AppModulesFileWriter;
