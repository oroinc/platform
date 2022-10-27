class Node {
    constructor(nodes = [], attrs = {}) {
        this.nodes = nodes;
        this.attrs = attrs;
    }

    toString() {
        const attrs = Object.entries(this.attrs)
            .map(([name, value]) => `${name}: ${JSON.stringify(value)}`);

        const repr = this.nodes
            .map(node => {
                return String(node)
                    .split('\n')
                    .map(line => `    ${line}`)
                    .join('\n');
            });

        repr.unshift(`${this.constructor.name}(${attrs.join(', ')}`);

        if (repr.length > 1) {
            repr.push(')');
        } else {
            repr[repr.length - 1] += ')';
        }

        return repr.join('\n');
    }

    /**
     * @param {Compiler} compiler
     */
    compile(compiler) {
        this.nodes.forEach(node => node.compile(compiler));
    }

    /**
     * @param {Object} functions
     * @param {Object} values
     * @return {any[]}
     */
    evaluate(functions, values) {
        return this.nodes.map(node => node.evaluate(functions, values));
    }
}

export default Node;
