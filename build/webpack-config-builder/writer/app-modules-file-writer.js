const path = require('path');
const fs = require('file-system');

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
        let buildPath = path.join(output, 'app-modules.js');
        let content = 'export default [\n';
        appModules.forEach(appModule => {
            content += `    require('${appModule}'),\n`
        });
        content += '];\n';
        let filepath = path.resolve(this._publicPath + buildPath);
        fs.writeFileSync(filepath, content);

        return filepath;
    }
}

module.exports = AppModulesFileWriter;
