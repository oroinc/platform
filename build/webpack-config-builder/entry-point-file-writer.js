const path = require('path');
const fs = require('file-system');

class EntryPointFileWriter {
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
            input = input.replace(/\.[^/.]+$/, "");
            content += '@import "'+ baseInputPath + input + '";\n'
        });
        let scssFilepath = path.resolve(this._publicPath + output + '.scss');
        fs.writeFileSync(scssFilepath, content);

        let jsFilepath = path.resolve(this._publicPath + output.replace(/\.[^/.]+$/, "") + '.js');
        fs.writeFileSync(
            jsFilepath,
            'import \'./' + path.basename(scssFilepath) + '\';\n'
        );
        return jsFilepath;
    }
}

module.exports = EntryPointFileWriter;
