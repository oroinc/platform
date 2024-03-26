class Expression {
    /**
     * @param {string} expression An expression
     */
    constructor(expression) {
        this.expression = String(expression);
    }

    /**
     * Returns the expression.
     *
     * @return {string}
     */
    toString() {
        return this.expression;
    }
}

export default Expression;
