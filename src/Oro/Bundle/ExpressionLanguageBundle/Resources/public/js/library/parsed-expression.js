define(function(require) {
    'use strict';

    var Expression = require('oroexpressionlanguage/js/library/expression');

    /**
     * @param {string} expression  An expression
     * @param {Node} nodes  A Node representing the expression
     */
    function ParsedExpression(expression, nodes) {
        ParsedExpression.__super__.constructor.call(this, expression);
        this.nodes = nodes;
    }

    ParsedExpression.prototype = Object.create(Expression.prototype);
    ParsedExpression.__super__ = Expression.prototype;

    Object.assign(ParsedExpression.prototype, {
        constructor: ParsedExpression,

        /**
         * Returns the nodes.
         *
         * @return {Node}
         */
        getNodes: function() {
            return this.nodes;
        }
    });

    return ParsedExpression;
});
