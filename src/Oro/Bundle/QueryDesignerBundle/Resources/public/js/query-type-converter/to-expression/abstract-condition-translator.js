define(function(require) {
    'use strict';

    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');

    /**
     * Defines interface and implements base functionality of ConditionTranslatorToExpression
     */
    var AbstractConditionTranslator = function AbstractConditionTranslatorToExpression() {};

    Object.assign(AbstractConditionTranslator.prototype, {
        constructor: AbstractConditionTranslator,

        /**
         * Builds JSON validation schema
         *
         * @return {Object}
         * @protected
         * @abstract
         */
        getConditionSchema: function() {
            throw new Error(
                'Method `getConditionSchema` has to be defined in descendant ConditionTranslatorToExpression');
        },

        /**
         * Takes condition object and checks if it has valid structure to be translated to AST
         *
         * @param {Object} condition
         * @return {boolean}
         * @protected
         */
        test: function(condition) {
            var schema = this.getConditionSchema();
            return jsonSchemaValidator.validate(schema, condition);
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
         * @return {Node|null} ExpressionLanguage AST node
         * @protected
         * @abstract
         */
        translate: function(condition) {
            throw new Error('Method `translate` has to be defined in descendant FilterTranslatorToExpression');
        }
    });

    return AbstractConditionTranslator;
});
