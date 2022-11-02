import Expression from './expression';

class ParsedExpression extends Expression {
    /**
     * @param {string} expression  An expression
     * @param {Node} nodes  A Node representing the expression
     */
    constructor(expression, nodes) {
        super(expression);
        this.nodes = nodes;
    }

    /**
     * Returns the nodes.
     *
     * @return {Node}
     */
    getNodes() {
        return this.nodes;
    }
}

export default ParsedExpression;
