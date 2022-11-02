import _ from 'underscore';
import addcslashes from 'oroexpressionlanguage/lib/php-to-js/addcslashes';
import ASTNodeWrapper from 'oroexpressionlanguage/js/ast-node-wrapper';
import * as expressionLanguageLibrary from 'oroexpressionlanguage/js/expression-language-library';

const {GetAttrNode, UnaryNode, BinaryNode, ConstantNode, Parser, ArrayNode} = expressionLanguageLibrary;

class ToExpressionCompiler {
    /**
     * Compiles AST node back to expression string
     *
     * @param {Node} node
     * @param {Array.<string>|Object=null} names names that used by parser of ExpressionLanguage instance
     * @returns {string}
     */
    compile(node, names = null) {
        /** @property {Array.<string>|Object} */
        this.names = names;
        return String(this._compile(new ASTNodeWrapper(node)));
    }

    /**
     * Compiles wrapped AST node to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @throws {Error} In case attempt to compile unsupported type of node
     * @protected
     */
    _compile(node) {
        const constructor = node.origin().constructor;
        // retrieves original name of the Node constructor (workaround for broken after minification names)
        const name = constructor && _.findKey(expressionLanguageLibrary, item => item === constructor);
        if (name) {
            const methodName = `_compile${name}`;
            if (_.isFunction(this[methodName])) {
                return this[methodName](node);
            }
        }
        throw new Error('Attempt to compile unsupported type of node');
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileConstantNode(node) {
        return this.format(node.attr('value'));
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileNameNode(node) {
        const name = node.attr('name');
        if (_.isObject(this.names) && !_.isArray(this.names) && name in this.names) {
            return this.names[name];
        }
        return name;
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileGetAttrNode(node) {
        const type = node.attr('type');
        let expression = this._compile(node.child(0));
        if (type === GetAttrNode.ARRAY_CALL) {
            expression += `[${this._compile(node.child(1))}]`;
        } else {
            expression += '.' + node.child(1).attr('value');
            if (type === GetAttrNode.METHOD_CALL) {
                expression += `(${this._compile(node.child(2))})`;
            }
        }
        return expression;
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileArgumentsNode(node) {
        const pairs = this._getKeyValuePairs(node);

        return _.pluck(pairs, 'value').join(', ');
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileArrayNode(node) {
        let pairs = this._getKeyValuePairs(node);

        if (_.isEqual(_.range(pairs.length), _.pluck(pairs, 'key'))) {
            return `[${_.pluck(pairs, 'value').join(', ')}]`;
        } else {
            pairs = _.map(pairs, pair => pair.key + ': ' + pair.value);
            return `{${pairs.join(', ')}}`;
        }
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileFunctionNode(node) {
        const compiledArgs = node.child(0).origin().nodes
            .map(originNode => this._compile(new ASTNodeWrapper(originNode)));
        return `${node.attr('name')}(${compiledArgs.join(', ')})`;
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileUnaryNode(node) {
        let expression = node.attr('operator');

        if (/\w/.test(expression)) {
            // to avoid junction operator with operand
            expression += ' ';
        }

        expression += this._compile(node.child(0));

        if (this._isNeedBraces(node)) {
            expression = `(${expression})`;
        }

        return expression;
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileBinaryNode(node) {
        let expression = this._compile(node.child(0)) + ' ' + node.attr('operator') + ' ' +
            this._compile(node.child(1));

        if (this._isNeedBraces(node)) {
            expression = `(${expression})`;
        }

        return expression;
    }

    /**
     * Compiles wrapped AST node of particular type to expression string
     *
     * @param {ASTNodeWrapper} node
     * @returns {string}
     * @protected
     */
    _compileConditionalNode(node) {
        return this._compile(node.child(0)) + ' ? ' + this._compile(node.child(1)) + ' : ' +
            this._compile(node.child(2));
    }

    /**
     * Tries to compile wrapped AST node as avoid wrap simple sting and number key in excess quotes
     *
     * @param {ASTNodeWrapper} node
     * @returns {number|string}
     * @protected
     */
    _compileObjectKey(node) {
        if (node.instanceOf(ConstantNode)) {
            return this.formatKey(node.attr('value'));
        } else {
            return this._compile(node);
        }
    }

    /**
     * Checks if node has parent node that contains operation with higher precedence
     * @param node
     * @returns {boolean}
     * @protected
     */
    _isNeedBraces(node) {
        return node.parent && node.parent.instanceOf(UnaryNode, BinaryNode) &&
            this._precedence(node) < this._precedence(node.parent);
    }

    /**
     * Finds value of unary or binary node's operation precedence
     *
     * @param {ASTNodeWrapper} node
     * @returns {number}
     * @throws {Error} when passed wrong type node
     * @protected
     */
    _precedence(node) {
        if (!node.instanceOf(UnaryNode, BinaryNode)) {
            throw new Error('The method supports only UnaryNode or BinaryNode');
        }

        const operator = node.attr('operator');
        const operatorsKey = node.instanceOf(UnaryNode) ? 'UNARY_OPERATORS' : 'BINARY_OPERATORS';
        const result = _.result(Parser[operatorsKey][operator], 'precedence');

        if (result === void 0) {
            throw new Error(`The compiler doesn't support \`${operator}\` operator`);
        }

        return result;
    }

    /**
     * Gets key-value pairs from ArrayNode and compiles them
     *
     * @param {ASTNodeWrapper} node
     * @returns {number}
     * @throws {Error} when passed wrong type node
     * @protected
     */
    _getKeyValuePairs(node) {
        if (!node.instanceOf(ArrayNode)) {
            throw new Error('The method supports only ArrayNode');
        }

        const pairs = node.origin().getKeyValuePairs();

        return _.map(pairs, ({key, value}) => ({
            key: this._compileObjectKey(new ASTNodeWrapper(key)),
            value: this._compile(new ASTNodeWrapper(value))
        }));
    }

    /**
     * Formats value depends of its type
     *
     * @param {*} value
     * @return {string}
     */
    format(value) {
        let result = '';
        if (value === null || typeof value === 'number' || typeof value === 'boolean') {
            result = String(value);
        } else if (_.isArray(value)) {
            const items = value.map(val => this.format(val));
            result = `[${items.join(', ')}]`;
        } else if (typeof value === 'object') {
            result = '{';
            let first = true;
            for (const key in value) {
                if (!value.hasOwnProperty(key)) {
                    continue;
                }
                if (!first) {
                    result += ', ';
                }
                first = false;
                result += this.formatKey(key) + ': ' + this.format(value[key]);
            }
            result += '}';
        } else {
            result = `"${addcslashes(value, '\"\\')}"`;
        }
        return result;
    }

    /**
     * Formats value as key of object, e.g. adds quotes only if it needs
     *
     * @param {*} value
     * @return {number|string}
     */
    formatKey(value) {
        const keyRegExp = /^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/;

        if (typeof value === 'number' || typeof value === 'string' && keyRegExp.test(value)) {
            return value;
        }

        return this.format(value);
    }
}

export default ToExpressionCompiler;
