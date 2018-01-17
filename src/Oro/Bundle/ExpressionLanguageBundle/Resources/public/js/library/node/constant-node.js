define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');

    /**
     * @param {*} value a constant value
     */
    function ConstantNode(value) {
        ConstantNode.__super__.call(this, [], {value: value});
    }

    ConstantNode.prototype = Object.create(Node.prototype);
    ConstantNode.__super__ = Node;

    Object.assign(ConstantNode.prototype, {
        constructor: ConstantNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            compiler.repr(this.attrs.value);
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            return this.attrs.value;
        }
    });

    return ConstantNode;
});
