define(function(require) {
    'use strict';

    var OriginalBinaryNode = require('oroexpressionlanguage/js/library/node/binary-node');

    /**
     * @inheritDoc
     */
    function BinaryNode(operator, left, right) {
        BinaryNode.__super__.constructor.call(this, operator, left, right);
    }

    BinaryNode.prototype = Object.create(OriginalBinaryNode.prototype);
    BinaryNode.__super__ = OriginalBinaryNode.prototype;

    Object.assign(BinaryNode.prototype, {
        constructor: BinaryNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            var operator = this.attrs.operator;
            var equalOperators = {
                '=': '===',
                '!=': '!=='
            };

            if (operator in equalOperators) {
                compiler
                    .raw('(')
                    .compile(this.nodes[0])
                    .raw(' ')
                    .raw(equalOperators[operator])
                    .raw(' ')
                    .compile(this.nodes[1])
                    .raw(')');
            } else {
                BinaryNode.__super__.compile.call(this, compiler);
            }
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            var left;
            var right;
            var operator = this.attrs.operator;

            if (['=', '!='].indexOf(operator) !== -1) {
                left = this.nodes[0].evaluate(functions, values);
                right = this.nodes[1].evaluate(functions, values);
                switch (operator) {
                    case '=':
                        return left === right;
                    case '!=':
                        return left !== right;
                }
            } else {
                return BinaryNode.__super__.evaluate.call(this, functions, values);
            }
        }
    });

    return BinaryNode;
});
