const path = require('path');
const fs = require('file-system');

class DynamicImportsFileWriter {
    /**
     * @param {string} publicPath
     */
    constructor(publicPath) {
        this._publicPath = publicPath;
    }

    /**
     * Write modules.js file and return file path
     *
     * @param {Object} dynamicImports Map of module names with their chunk names
     * @param {string} output Output file path
     * @returns {string} JS file path of an output file
     */
    write(dynamicImports, output) {
        let buildPath =  path.join(output, 'dynamic-imports.js');
        let content = 'module.exports = {\n';
        for (let chunkName in dynamicImports) {
            dynamicImports[chunkName].forEach(function(moduleName) {
                content += `  "${moduleName}": function() { return import(/* webpackChunkName: "${chunkName}" */ "${moduleName}") },\n`
            });
        }
        content += '};\n';
        let filepath = path.resolve(this._publicPath + buildPath);
        fs.writeFileSync(filepath, content);
        return filepath;
    }
}

module.exports = DynamicImportsFileWriter;
