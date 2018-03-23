define(function(require) {
    'use strict';

    var _ = require('underscore');
    var addcslashes = require('oroexpressionlanguage/lib/php-to-js/addcslashes');
    var ASTNodeWrapper = require('oroexpressionlanguage/js/ast-node-wrapper');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var UnaryNode = ExpressionLanguageLibrary.UnaryNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;

    function ToExpressionCompiler() {}

    ToExpressionCompiler.prototype = {
        constructor: ToExpressionCompiler,

        /**
         * Names that used by parser of ExpressionLanguage instance
         *
         * @type {Array.<string>|Object}
         */
        names: null,

        /**
         * Compiles AST node back to expression string
         *
         * @param {Node} node
         * @param {Array.<string>|Object=null} names
         * @returns {string}
         */
        compile: function(node, names) {
            this.names = names || null;
            return String(this._compile(new ASTNodeWrapper(node)));
        },

        /**
         * Compiles wrapped AST node to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @throws {Error} In case attempt to compile unsupported type of node
         * @protected
         */
        _compile: function(node) {
            var constructor = node.origin().constructor;

            if (constructor && ExpressionLanguageLibrary[constructor.name] === constructor) {
                var methodName = '_compile' + constructor.name;

                if (_.isFunction(this[methodName])) {
                    return this[methodName](node);
                }
            }
            throw new Error('Attempt to compile unsupported type of node');
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileConstantNode: function(node) {
            return this.format(node.attr('value'));
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileNameNode: function(node) {
            var name = node.attr('name');
            if (_.isObject(this.names) && !_.isArray(this.names) && name in this.names) {
                return this.names[name];
            }
            return name;
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileGetAttrNode: function(node) {
            var type = node.attr('type');
            var expression = this._compile(node.child(0));
            if (type === GetAttrNode.ARRAY_CALL) {
                expression += '[' + this._compile(node.child(1)) + ']';
            } else {
                expression += '.' + node.child(1).attr('value');
                if (type === GetAttrNode.METHOD_CALL) {
                    expression += '(' + this._compile(node.child(2)) + ')';
                }
            }
            return expression;
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileArgumentsNode: function(node) {
            var pairs = this._getKeyValuePairs(node);

            return _.pluck(pairs, 'value').join(', ');
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileArrayNode: function(node) {
            var pairs = this._getKeyValuePairs(node);

            if (_.isEqual(_.range(pairs.length), _.pluck(pairs, 'key'))) {
                return '[' + _.pluck(pairs, 'value').join(', ') + ']';
            } else {
                pairs = _.map(pairs, function(pair) {
                    return pair.key + ': ' + pair.value;
                });
                return '{' + pairs.join(', ') + '}';
            }
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileFunctionNode: function(node) {
            var compiledArgs = _.map(node.child(0).origin().nodes, function(originNode) {
                return this._compile(new ASTNodeWrapper(originNode));
            }, this);
            return node.attr('name') + '(' + compiledArgs.join(', ') + ')';
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileUnaryNode: function(node) {
            var expression = node.attr('operator');

            if (/\w/.test(expression)) {
                // to avoid junction operator with operand
                expression += ' ';
            }

            expression += this._compile(node.child(0));

            if (this._isNeedBraces(node)) {
                expression = '(' + expression + ')';
            }

            return expression;
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileBinaryNode: function(node) {
            var expression = this._compile(node.child(0)) + ' ' + node.attr('operator') + ' ' +
                this._compile(node.child(1));

            if (this._isNeedBraces(node)) {
                expression = '(' + expression + ')';
            }

            return expression;
        },

        /**
         * Compiles wrapped AST node of particular type to expression string
         *
         * @param {ASTNodeWrapper} node
         * @returns {string}
         * @protected
         */
        _compileConditionalNode: function(node) {
            return this._compile(node.child(0)) + ' ? ' + this._compile(node.child(1)) + ' : ' +
                this._compile(node.child(2));
        },

        /**
         * Tries to compile wrapped AST node as avoid wrap simple sting and number key in excess quotes
         *
         * @param {ASTNodeWrapper} node
         * @returns {number|string}
         * @protected
         */
        _compileObjectKey: function(node) {
            if (node.instanceOf(ExpressionLanguageLibrary.ConstantNode)) {
                return this.formatKey(node.attr('value'));
            } else {
                return this._compile(node);
            }
        },

        /**
         * Checks if node has parent node that contains operation with higher precedence
         * @param node
         * @returns {boolean}
         * @protected
         */
        _isNeedBraces: function(node) {
            return node.parent && node.parent.instanceOf(UnaryNode, BinaryNode) &&
                this._precedence(node) < this._precedence(node.parent);
        },

        /**
         * Finds value of unary or binary node's operation precedence
         *
         * @param {ASTNodeWrapper} node
         * @returns {number}
         * @throws {Error} when passed wrong type node
         * @protected
         */
        _precedence: function(node) {
            if (!node.instanceOf(UnaryNode, BinaryNode)) {
                throw new Error('The method supports only UnaryNode or BinaryNode');
            }

            var operator = node.attr('operator');
            var operatorsKey = node.instanceOf(UnaryNode) ? 'unaryOperators' : 'binaryOperators';
            var result = _.result(ExpressionLanguageLibrary.Parser.prototype[operatorsKey][operator], 'precedence');

            if (result === void 0) {
                throw new Error('The compiler doesn\'t support `' + operator + '` operator');
            }

            return result;
        },

        /**
         * Gets key-value pairs from ArrayNode and compiles them
         *
         * @param {ASTNodeWrapper} node
         * @returns {number}
         * @throws {Error} when passed wrong type node
         * @protected
         */
        _getKeyValuePairs: function(node) {
            if (!node.instanceOf(ExpressionLanguageLibrary.ArrayNode)) {
                throw new Error('The method supports only ArrayNode');
            }

            var pairs = node.origin().getKeyValuePairs();

            return _.map(pairs, function(pair) {
                return {
                    key: this._compileObjectKey(new ASTNodeWrapper(pair.key)),
                    value: this._compile(new ASTNodeWrapper(pair.value))
                };
            }, this);
        },

        /**
         * Formats value depends of its type
         *
         * @param {*} value
         * @return {string}
         */
        format: function(value) {
            var result = '';
            if (value === null || typeof value === 'number' || typeof value === 'boolean') {
                result = String(value);
            } else if (_.isArray(value)) {
                var items = value.map(function(val) {
                    return this.format(val);
                }.bind(this));
                result = '[' + items.join(', ') + ']';
            } else if (typeof value === 'object') {
                result = '{';
                var first = true;
                for (var key in value) {
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
                result = '"' + addcslashes(value, '\"\\') + '"';
            }
            return result;
        },

        /**
         * Formats value as key of object, e.g. adds quotes only if it needs
         *
         * @param {*} value
         * @return {number|string}
         */
        formatKey: function(value) {
            var keyRegExp = /^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/;

            if (typeof value === 'number' || typeof value === 'string' && keyRegExp.test(value)) {
                return value;
            }

            return this.format(value);
        }
    };

    return ToExpressionCompiler;
});
