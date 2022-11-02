import Node from './node';

class UnaryNode extends Node {
    static OPERATORS = {
        '!': '!',
        'not': '!',
        '+': '+',
        '-': '-'
    };

    /**
     * @param {string} operator
     * @param {Node} node
     */
    constructor(operator, node) {
        super([node], {operator: operator});
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        compiler
            .raw('(')
            .raw(UnaryNode.OPERATORS[this.attrs.operator])
            .compile(this.nodes[0])
            .raw(')');
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        const value = this.nodes[0].evaluate(functions, values);
        switch (this.attrs.operator) {
            case 'not':
            case '!':
                return !value;
            case '-':
                return -value;
        }
        return value;
    }
}

export default UnaryNode;
