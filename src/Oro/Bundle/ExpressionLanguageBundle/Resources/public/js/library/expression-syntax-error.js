class ExpressionSyntaxError extends Error {
    /**
     * Expression syntax error
     *
     * @param {string} message
     * @param {number=} cursor
     * @param {string} [expression]
     */
    constructor(message, cursor = 0, expression) {
        message += ` around position ${cursor}`;
        if (expression) {
            message += ` for expression \`${expression}\``;
        }
        message += '.';

        super(message);
    }
}

export default ExpressionSyntaxError;
