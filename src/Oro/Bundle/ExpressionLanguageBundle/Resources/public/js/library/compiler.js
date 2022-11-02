import addcslashes from 'oroexpressionlanguage/lib/php-to-js/addcslashes';

class Compiler {
    /**
     * @param {Object.<string, ExpressionFunction>} functions an list of declared functions
     */
    constructor(functions) {
        this.source = '';
        this.functions = functions;
    }

    /**
     * @param {string} name
     * @return {ExpressionFunction}
     */
    getFunction(name) {
        return this.functions[name];
    }

    /**
     * Gets the current JS code after compilation
     *
     * @return {string}
     */
    getSource() {
        return this.source;
    }

    /**
     * Resets collected source
     *
     * @return {Compiler}
     */
    reset() {
        this.source = '';
        return this;
    }

    /**
     * Compiles a node
     *
     * @param {Node} node
     * @return {Compiler}
     */
    compile(node) {
        node.compile(this);
        return this;
    }

    /**
     * Compiles the node and returns source result without changing the source of compiler
     *
     * @param {Node} node
     * @return {string}
     */
    subcompile(node) {
        const currentSource = this.source;
        this.source = '';
        node.compile(this);
        const source = this.source;
        this.source = currentSource;
        return source;
    }

    /**
     * Adds a raw string to the compiled code
     *
     * @param {string} value
     * @return {Compiler}
     */
    raw(value) {
        this.source += value;
        return this;
    }

    /**
     * Adds a quoted string to the compiled code
     *
     * @param {string} value
     * @return {Compiler}
     */
    string(value) {
        this.source += `"${addcslashes(value, '\0\t\"\$\\')}"`;
        return this;
    }

    /**
     * Adds a code representation of value to the compiled code
     *
     * @param {*} value
     * @return {Compiler}
     */
    repr(value) {
        let first;
        if (typeof value === 'number') {
            this.raw(value);
        } else if (value === null) {
            this.raw('null');
        } else if (typeof value === 'boolean') {
            this.raw(value ? 'true' : 'false');
        } else if (Object.prototype.toString.call(value) === '[object Array]') {
            this.raw('[');
            first = true;
            value.forEach(function(val) {
                if (!first) {
                    this.raw(', ');
                }
                first = false;
                this.repr(val);
            }.bind(this));
            this.raw(']');
        } else if (typeof value === 'object') {
            this.raw('{');
            first = true;
            for (const key in value) {
                if (!value.hasOwnProperty(key)) {
                    continue;
                }
                if (!first) {
                    this.raw(', ');
                }
                first = false;
                this.repr(key);
                this.raw(': ');
                this.repr(value[key]);
            }
            this.raw('}');
        } else {
            this.string(value);
        }
        return this;
    }
}

export default Compiler;
