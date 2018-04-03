define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Node = require('oroexpressionlanguage/js/library/node/node');
    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');

    var expressionLanguageTools = {
        /**
         * Checks if node is ArrayNode that can be represented as JS array and contains ConstantNode as items
         *
         * @param {ArrayNode} node
         * @return {boolean}
         * @protected
         */
        isIndexedArrayNode: function(node) {
            for (var i = 0; i < node.nodes.length; i += 2) {
                if (!(node.nodes[i] instanceof ConstantNode) || node.nodes[i].attrs.value !== i / 2) {
                    return false;
                }
            }

            return true;
        },

        /**
         * Constructs ArrayNode with received items
         *
         * @param {Object|Array.<*|[key, value]>} values - object or array where each value can be primitive, Node, or
         * key-value pair
         * @returns {ArrayNode}
         */
        createArrayNode: function(values) {
            var key;
            var value;
            var node = new ArrayNode();

            if (!_.isArray(values)) {
                values = _.pairs(values);
            }

            for (var i = 0; i < values.length; i++) {
                if (_.isArray(values[i])) {
                    key = values[i][0];
                    if (!(key instanceof Node)) {
                        key = new ConstantNode(key);
                    }
                    value = values[i][1];
                } else {
                    key = void 0;
                    value = values[i];
                }

                if (!(value instanceof Node)) {
                    value = new ConstantNode(value);
                }

                node.addElement(value, key);
            }

            return node;
        }
    };

    return expressionLanguageTools;
});
