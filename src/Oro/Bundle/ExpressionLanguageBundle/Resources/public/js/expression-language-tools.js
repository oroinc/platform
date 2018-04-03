define(function(require) {
    'use strict';

    var expressionLanguageTools;
    var _ = require('underscore');
    var ArgumentsNode = require('oroexpressionlanguage/js/library/node/arguments-node');
    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var GetAttrNode = require('oroexpressionlanguage/js/library/node/get-attr-node');
    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');
    var Node = require('oroexpressionlanguage/js/library/node/node');

    expressionLanguageTools = {
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
        },

        /**
         * Creates chain of GetAttrNode on base of property names list
         *
         * @param {Array.<string>} props list of properties
         *   like `['foo', 'bar', 'baz', 'qoo']` or `'foo.bar.baz.qoo'.split('.')`
         * @param {GetAttrNode|NameNode} [node]
         * @return {GetAttrNode}
         */
        createGetAttrNode: function(props, node) {
            var name = props[0];
            if (!node) {
                node = new NameNode(name);
            } else {
                node = new GetAttrNode(
                    node,
                    new ConstantNode(name),
                    new ArgumentsNode(),
                    GetAttrNode.PROPERTY_CALL
                );
            }
            if (props.length === 1) {
                return node;
            }
            return expressionLanguageTools.createGetAttrNode(props.slice(1), node);
        }
    };

    return expressionLanguageTools;
});
