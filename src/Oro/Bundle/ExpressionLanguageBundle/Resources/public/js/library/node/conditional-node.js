import Node from './node';

class ConditionalNode extends Node {
    /**
     * @param {Node} expr1 condition expression
     * @param {Node} expr2 then expression
     * @param {Node} expr3 else expression
     */
    constructor(expr1, expr2, expr3) {
        const nodes = [expr1, expr2, expr3];
        super(nodes);
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        compiler
            .raw('((')
            .compile(this.nodes[0])
            .raw(') ? (')
            .compile(this.nodes[1])
            .raw(') : (')
            .compile(this.nodes[2])
            .raw('))');
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        if (this.nodes[0].evaluate(functions, values)) {
            return this.nodes[1].evaluate(functions, values);
        }
        return this.nodes[2].evaluate(functions, values);
    }
}

export default ConditionalNode;
