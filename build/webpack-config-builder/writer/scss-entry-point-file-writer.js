const path = require('path');
const fs = require('fs');

class SCSSEntryPointFileWriter {
    /**
     * @param {string} publicPath
     */
    constructor(publicPath) {
        this._publicPath = publicPath;
    }

    /**
     * Write entry point file and return file path
     *
     * @param {string} baseInputPath base path for input files
     * @param {Array} inputs List of inputs
     * @param {string} output Output file path
     * @returns {string} JS file path of an output file
     */
    write(baseInputPath, inputs, output) {
        let content = '';
        inputs.forEach(input => {
            // input = path.resolve(this._publicPath, input);
            input = input.replace(/\.[^/.]+$/, '');
            content += '@import "'+ baseInputPath + input + '";\n';
        });
        const scssFilepath = path.resolve(this._publicPath + output + '.scss');
        fs.mkdirSync(path.dirname(scssFilepath), {recursive: true});
        fs.writeFileSync(scssFilepath, content);

        const jsFilepath = path.resolve(this._publicPath + output.replace(/\.[^/.]+$/, '') + '.scss.js');
        fs.mkdirSync(path.dirname(jsFilepath), {recursive: true});
        fs.writeFileSync(
            jsFilepath,
            'import \'./' + path.basename(scssFilepath) + '\';\n'
        );
        return jsFilepath;
    }
}

module.exports = SCSSEntryPointFileWriter;
