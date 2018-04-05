define(function(require) {
    'use strict';

    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');
    var StringFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator');

    /**
     * Defines interface and implements base functionality of ConditionTranslatorToExpression
     *
     * @param {FieldIdTranslatorToExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @constructor
     * @throws TypeError if instance of FieldIdTranslatorToExpression is missing
     */
    var AbstractConditionTranslator = function AbstractConditionTranslatorToExpression(
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
        // TODO: move store of available filter translators to separate module
        this.filterTranslators = {
            string: new StringFilterTranslator()
        };
    };

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
         * Finds appropriate filter translate
         * @param {Object} condition
         * @return {AbstractFilterTranslator|null}
         * @protected
         */
        resolveFilterTranslator: function(condition) {
            var filterConfig = this.filterConfigProvider.getFilterConfigByName(condition.criterion.filter);
            var filterTranslator = this.filterTranslators[filterConfig.type];

            if (filterTranslator && filterTranslator.test(condition.criterion.data, filterConfig)) {
                return filterTranslator;
            }

            return null;
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
                var filterTranslator = this.resolveFilterTranslator(condition);
                if (filterTranslator) {
                    result = this.translate(condition, filterTranslator);
                }
            }
            return result;
        },

        /**
         * Takes condition object and appropriated filter translator translates it to ExpressionLanguage AST
         *
         * @param {Object} condition
         * @param {AbstractFilterTranslator} filterTranslator
         * @return {Node|null} ExpressionLanguage AST node
         * @protected
         * @abstract
         */
        translate: function(condition, filterTranslator) {
            throw new Error('Method `translate` has to be defined in descendant FilterTranslatorToExpression');
        }
    });

    /**
     * @export oroquerydesigner/js/query-type-converter/to-expression/abstract-condition-translator
     */
    return AbstractConditionTranslator;
});
