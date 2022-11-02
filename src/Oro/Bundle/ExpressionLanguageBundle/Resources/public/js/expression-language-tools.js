import _ from 'underscore';
import ArgumentsNode from 'oroexpressionlanguage/js/library/node/arguments-node';
import ArrayNode from 'oroexpressionlanguage/js/library/node/array-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import FunctionNode from 'oroexpressionlanguage/js/library/node/function-node';
import GetAttrNode from 'oroexpressionlanguage/js/library/node/get-attr-node';
import NameNode from 'oroexpressionlanguage/js/library/node/name-node';
import Node from 'oroexpressionlanguage/js/library/node/node';

const expressionLanguageTools = {
    /**
     * Checks if node is ArrayNode that can be represented as JS array and contains ConstantNode as items
     *
     * @param {ArrayNode} node
     * @return {boolean}
     * @protected
     */
    isIndexedArrayNode(node) {
        for (let i = 0; i < node.nodes.length; i += 2) {
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
    createArrayNode(values) {
        let key;
        let value;
        const node = new ArrayNode();

        if (!_.isArray(values)) {
            values = _.pairs(values);
        }

        for (let i = 0; i < values.length; i++) {
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
    createGetAttrNode(props, node) {
        if (_.isString(props)) {
            props = props.split('.');
        }
        const name = props[0];
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
    createFunctionNode(funcName, args) {
        args = (args || []).map(value => value instanceof Node ? value : new ConstantNode(value));
        return new FunctionNode(funcName, new Node(args));
    },


    /**
     * Compares two nodes of AST and returns true if they have the same type, structure and attributes
     *
     * @param {Node} node1
     * @param {Node} node2
     * @return {boolean}
     */
    compareAST(node1, node2) {
        return node1 instanceof Node &&
            node2 instanceof Node &&
            node1.constructor === node2.constructor &&
            // same set of attributes
            _.every(node1.attrs, (value, name) => node2.attrs[name] === value) &&
            // same sub-nodes
            node1.nodes.length === node2.nodes.length &&
            _.every(node1.nodes, (node, index) => expressionLanguageTools.compareAST(node, node2.nodes[index]));
    },

    cloneAST(node) {
        let result;
        let args;
        const Constructor = node.constructor;

        switch (Constructor.name) {
            case 'ArgumentsNode':
            case 'ArrayNode':
                result = new Constructor();
                node.getKeyValuePairs().forEach(pair => {
                    result.addElement(
                        expressionLanguageTools.cloneAST(pair.value),
                        expressionLanguageTools.cloneAST(pair.key)
                    );
                });
                result.index = node.index;
                break;
            case 'Node':
                result = new Node(
                    node.nodes.map(expressionLanguageTools.cloneAST),
                    Object.assign({}, node.attrs)
                );
                break;
            case 'GetAttrNode':
                args = node.nodes.map(expressionLanguageTools.cloneAST).concat(Object.values(node.attrs));
                result = new (Function.prototype.bind.apply(Constructor, [null].concat(args)))();
                break;
            case 'BinaryNode':
            case 'ConditionalNode':
            case 'ConstantNode':
            case 'FunctionNode':
            case 'NameNode':
            case 'UnaryNode':
                args = Object.values(node.attrs).concat(node.nodes.map(expressionLanguageTools.cloneAST));
                result = new (Function.prototype.bind.apply(Constructor, [null].concat(args)))();
                break;
            default:
                throw new Error('Can not clone unknown type of AST node `' + node.constructor.name + '`');
        }

        return result;
    }
};

export default expressionLanguageTools;
