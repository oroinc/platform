define(function(require) {
    'use strict';

    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');

    /**
     * Defines interface and implements base functionality of ConditionTranslatorToExpression
     *
     * @param {FieldIdTranslatorToExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @param {TranslatorProvider} filterTranslatorProvider
     * @constructor
     * @throws TypeError if some required argument is missing
     */
    var AbstractConditionTranslator = function AbstractConditionTranslatorToExpression(
        fieldIdTranslator,
        filterConfigProvider,
        filterTranslatorProvider
    ) {
        if (!fieldIdTranslator) {
            throw new TypeError(
                'Instance of `FieldIdTranslator` is required for `AbstractConditionTranslatorToExpression`');
        }
        if (!filterConfigProvider) {
            throw new TypeError(
                'Instance of `FilterConfigProvider` is required for `AbstractConditionTranslatorToExpression`');
        }
        if (!filterTranslatorProvider) {
            throw new TypeError(
                'Instance of `TranslatorProvider` is required for `AbstractConditionTranslatorToExpression`');
        }

        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigProvider = filterConfigProvider;
        this.filterTranslatorProvider = filterTranslatorProvider;
    };

    Object.assign(AbstractConditionTranslator.prototype, {
        constructor: AbstractConditionTranslator,

        /**
         * @type {FieldIdTranslatorToExpression}
         */
        fieldIdTranslator: null,

        /**
         * @type {FilterConfigProvider}
         */
        filterConfigProvider: null,

        /**
         * @type {TranslatorProvider}
         */
        filterTranslatorProvider: null,

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
        },

        /**
         * Finds filter translate by its name
         *
         * @param {string} filterName
         * @return {AbstractFilterTranslatorToExpression|null}
         * @protected
         */
        resolveFilterTranslator: function(filterName) {
            var FilterTranslator;
            var filterTranslator;
            var filterConfig = this.filterConfigProvider.getFilterConfigByName(filterName);
            if (
                filterConfig &&
                (FilterTranslator = this.filterTranslatorProvider.getTranslatorConstructor(filterConfig.type))
            ) {
                filterTranslator = new FilterTranslator(filterConfig);
            }
            return filterTranslator || null;
        }
    });

    return AbstractConditionTranslator;
});
