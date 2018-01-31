define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');

    /**
     * @param {Node} expr1 condition expression
     * @param {Node} expr2 then expression
     * @param {Node} expr3 else expression
     */
    function ConditionalNode(expr1, expr2, expr3) {
        var nodes = [expr1, expr2, expr3];
        ConditionalNode.__super__.constructor.call(this, nodes);
    }

    ConditionalNode.prototype = Object.create(Node.prototype);
    ConditionalNode.__super__ = Node.prototype;

    Object.assign(ConditionalNode.prototype, {
        constructor: ConditionalNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            compiler
                .raw('((')
                .compile(this.nodes[0])
                .raw(') ? (')
                .compile(this.nodes[1])
                .raw(') : (')
                .compile(this.nodes[2])
                .raw('))');
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            if (this.nodes[0].evaluate(functions, values)) {
                return this.nodes[1].evaluate(functions, values);
            }
            return this.nodes[2].evaluate(functions, values);
        }
    });

    return ConditionalNode;
});
