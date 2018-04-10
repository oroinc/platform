define(function(require) {
    'use strict';

    var expressionLanguageTools;
    var _ = require('underscore');
    var ArgumentsNode = require('oroexpressionlanguage/js/library/node/arguments-node');
    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var FunctionNode = require('oroexpressionlanguage/js/library/node/function-node');
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
         * Creates nested GetAttrNode's AST on base of property names list
         *
         * @param {Array.<string>|string} props list of properties
         *   like `['foo', 'bar', 'baz', 'qoo']` or simply `'foo.bar.baz.qoo'`
         * @param {GetAttrNode|NameNode} [node]
         * @return {GetAttrNode}
         */
        createGetAttrNode: function(props, node) {
            if (_.isString(props)) {
                props = props.split('.');
            }
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
        },

        /**
         * Creates FunctionNode for provided function name and list of arguments (optional)
         *
         * @param {string} funcName
         * @param {Array.<string|number|boolean|null|Node>} [args]
         * @return {FunctionNode}
         */
        createFunctionNode: function(funcName, args) {
            args = (args || []).map(function(value) {
                return value instanceof Node ? value : new ConstantNode(value);
            });
            return new FunctionNode(funcName, new Node(args));
        },


        /**
         * Compares two nodes of AST and returns true if they have the same type, structure and attributes
         *
         * @param {Node} node1
         * @param {Node} node2
         * @return {boolean}
         */
        compareAST: function(node1, node2) {
            return node1 instanceof Node &&
                node2 instanceof Node &&
                node1.constructor === node2.constructor &&
                // same set of attributes
                _.every(node1.attrs, function(value, name) {
                    return node2.attrs[name] === value;
                }) &&
                // same sub-nodes
                node1.nodes.length === node2.nodes.length &&
                _.every(node1.nodes, function(node, index) {
                    return expressionLanguageTools.compareAST(node, node2.nodes[index]);
                });
        }
    };

    return expressionLanguageTools;
});
