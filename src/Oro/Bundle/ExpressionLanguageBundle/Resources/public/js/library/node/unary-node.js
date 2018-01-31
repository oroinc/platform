define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');

    /**
     * @param {string} operator
     * @param {Node} node
     */
    function UnaryNode(operator, node) {
        UnaryNode.__super__.constructor.call(this, [node], {operator: operator});
    }

    UnaryNode.prototype = Object.create(Node.prototype);
    UnaryNode.__super__ = Node.prototype;
    UnaryNode.OPERATORS = {
        '!': '!',
        'not': '!',
        '+': '+',
        '-': '-'
    };

    Object.assign(UnaryNode.prototype, {
        constructor: UnaryNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            compiler
                .raw('(')
                .raw(UnaryNode.OPERATORS[this.attrs.operator])
                .compile(this.nodes[0])
                .raw(')');
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            var value = this.nodes[0].evaluate(functions, values);
            switch (this.attrs.operator) {
                case 'not':
                case '!':
                    return !value;
                case '-':
                    return -value;
            }
            return value;
        }
    });

    return UnaryNode;
});
