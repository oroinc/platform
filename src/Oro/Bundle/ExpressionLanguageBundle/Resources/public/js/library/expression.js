define(function() {
    'use strict';

    /**
     * @param {string} expression  An expression
     */
    function Expression(expression) {
        this.expression = String(expression);
    }

    Expression.prototype = {
        constructor: Expression,

        /**
         * Returns the expression.
         *
         * @return {string}
         */
        toString: function() {
            return this.expression;
        }
    };

    return Expression;
});
