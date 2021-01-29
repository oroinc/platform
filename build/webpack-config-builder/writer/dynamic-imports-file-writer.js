const path = require('path');
const fs = require('fs');

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
        const buildPath = path.join(output, 'dynamic-imports.js');
        let content = Object.entries(dynamicImports).map(([chunkName, moduleNames]) => {
            return moduleNames.map(moduleName =>
                `'${moduleName}': function() { return import(/* webpackChunkName: "${chunkName}" */ '${moduleName}') }`
            );
        });
        content = `module.exports = {\n  ${content.flat().join(',\n  ')}\n};\n`;
        const filepath = path.resolve(this._publicPath + buildPath);
        fs.mkdirSync(path.dirname(filepath), {recursive: true});
        fs.writeFileSync(filepath, content);
        return filepath;
    }
}

module.exports = DynamicImportsFileWriter;
