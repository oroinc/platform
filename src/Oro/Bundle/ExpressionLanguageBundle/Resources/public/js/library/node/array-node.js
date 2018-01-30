define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');

    function ArrayNode() {
        this.index = -1;
        ArrayNode.__super__.call(this);
    }

    ArrayNode.prototype = Object.create(Node.prototype);
    ArrayNode.__super__ = Node;

    Object.assign(ArrayNode.prototype, {
        constructor: ArrayNode,

        /**
         * Adds value and key nodes as sub-nodes of array node
         *
         * @param {Node} value
         * @param {Node} [key]
         */
        addElement: function(value, key) {
            if (key === void 0) {
                key = new ConstantNode(++this.index);
            }

            this.nodes.push(key, value);
        },

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            compiler.raw('{');
            this.compileArguments(compiler);
            compiler.raw('}');
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            var result = {};
            var pairs = this.getKeyValuePairs();
            pairs.forEach(function(pair) {
                var key = pair.key.evaluate(functions, values);
                result[key] = pair.value.evaluate(functions, values);
            });
            return result;
        },

        /**
         * @return {Array.<{key: string, value: *}>}
         * @protected
         */
        getKeyValuePairs: function() {
            var pairs = [];
            this.nodes.forEach(function(node, i) {
                if (i % 2) {
                    pairs[pairs.length - 1].value = node;
                } else {
                    pairs.push({key: node});
                }
            });
            return pairs;
        },

        /**
         * @param {Compiler} compiler
         * @param {boolean} [withKeys]
         * @protected
         */
        compileArguments: function(compiler, withKeys) {
            if (withKeys === void 0) {
                withKeys = true;
            }
            var pairs = this.getKeyValuePairs();
            pairs.forEach(function(pair, i) {
                if (i !== 0) {
                    compiler.raw(', ');
                }

                if (withKeys) {
                    compiler
                        .compile(pair.key)
                        .raw(': ');
                }

                compiler.compile(pair.value);
            });
        }
    });

    return ArrayNode;
});
