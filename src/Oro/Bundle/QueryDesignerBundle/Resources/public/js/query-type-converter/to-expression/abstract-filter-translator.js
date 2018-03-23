define(function() {
    'use strict';

    /**
     * Defines interface and implements base functionality of FilterTranslatorToExpression
     *
     * @param {FieldIdTranslatorToExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @constructor
     * @throws TypeError if instance of FieldIdTranslatorToExpression is missing
     */
    var AbstractFilterTranslator = function AbstractFilterTranslatorToExpression(
        fieldIdTranslator,
        filterConfigProvider
    ) {
        if (!fieldIdTranslator) {
            throw new TypeError(
                'Instance of `FieldIdTranslatorToExpression` is required for `FilterTranslatorToExpression`');
        }
        if (!filterConfigProvider) {
            throw new TypeError( 'Instance of `FilterConfigProvider` is required for `FilterTranslatorToExpression`');
        }
        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigProvider = filterConfigProvider;
    };

    Object.assign(AbstractFilterTranslator.prototype, {
        constructor: AbstractFilterTranslator,

        /**
         * Map object of possible filter criteria value to expression's operation
         * (has to defined in descendant FilterTranslatorToExpression)
         * @type {Object}
         */
        operatorMap: null,

        /**
         * Takes condition object and checks if it has valid structure and can be translated to AST
         *
         * @param {Object} condition
         * @return {boolean}
         * @protected
         * @abstract
         */
        test: function(condition) {
            throw new Error('Method `test` has to be defined in descendant FilterTranslatorToExpression');
        },

        /**
         * Takes condition object and translates it to ExpressionLanguage AST, if the condition passes the test.
         * Otherwise returns null
         *
         * @param {Object} condition
         * @return {Node|null} ExpressionLanguage AST node
         */
        tryToTranslate: function(condition) {
            var result = null;
            if (this.test(condition)) {
                result = this.translate(condition);
            }
            return result;
        },

        /**
         * Takes condition object and translates it to ExpressionLanguage AST
         *
         * @param {Object} condition
         * @return {Node} ExpressionLanguage AST node
         * @protected
         * @abstract
         */
        translate: function(condition) {
            throw new Error('Method `translate` has to be defined in descendant FilterTranslatorToExpression');
        }
    });

    return AbstractFilterTranslator;
});
