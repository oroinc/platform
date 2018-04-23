define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ToExpressionCompiler = require('oroexpressionlanguage/js/to-expression-compiler');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;

    var QueryConditionConverter = function QueryConditionConverterToExpression(conditionTranslators) {
        if (!conditionTranslators) {
            throw new TypeError(
                '`conditionTranslators` are required for `QueryConditionConverterToExpression`');
        }
        this.translators = conditionTranslators;
        this.compiler = new ToExpressionCompiler();
    };

    Object.assign(QueryConditionConverter.prototype, {
        constructor: QueryConditionConverter,

        /**
         * Converts expression to expression string
         *
         * @param {Array<Object|Array|string>} condition
         * @return {string|undefined}
         */
        convert: function(condition) {
            if (!this.test(condition)) {
                return void 0;
            } else if (condition.length === 0) {
                return '';
            }

            var ast = this.convertToAST(condition);

            return ast ? this.compiler.compile(ast) : void 0;
        },

        /**
         * Check order and type of elements in condition structure
         *
         * @param {Array<Object|Array|string>} condition
         * @return {boolean}
         */
        test: function(condition) {
            return _.isArray(condition) &&
                // empty or has odd length
                (condition.length === 0 || condition.length % 2 === 1) &&
                condition.every(function(item, index) {
                    var isOdd = index % 2 === 1;
                    // every element with odd index has to be string 'AND' or 'OR'
                    return isOdd && (item === 'AND' || item === 'OR') ||
                        // every element with even index has to be:
                        !isOdd && (
                        // not empty array with valid structure
                        _.isArray(item) && item.length > 0 && this.test(item) ||
                        // or plain object
                        _.isObject(item) && !_.isArray(item)
                    );
                }, this);
        },

        /**
         * Takes valid condition structure and converts it to AST
         *
         * @param {Array<Object|Array|string>} condition
         * @return {Node|null}
         */
        convertToAST: function(condition) {
            var mapped = condition.map(function(item) {
                var ast;
                if (_.isArray(item)) {
                    return this.convertToAST(item);
                } else if (_.isObject(item)) {
                    for (var i = 0; i < this.translators.length; i++) {
                        ast = this.translators[i].tryToTranslate(item);
                        if (ast) {
                            break;
                        }
                    }
                    return ast;
                } else {
                    return item.toLowerCase();
                }
            }, this);

            if (!_.every(mapped)) {
                return null;
            }

            var ast = mapped[0];
            for (var i = 1; i < mapped.length; i += 2) {
                ast = new BinaryNode(mapped[i], ast, mapped[i + 1]);
            }

            return ast;
        }
    });

    return QueryConditionConverter;
});
