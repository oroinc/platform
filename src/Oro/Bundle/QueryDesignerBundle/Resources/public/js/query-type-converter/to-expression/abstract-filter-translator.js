define(function(require) {
    'use strict';

    var _ = require('underscore');
    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');

    /**
     * Defines interface and implements base functionality of FilterTranslatorToExpression
     *
     * @param filterConfig
     * @constructor
     * @throws TypeError if filterConfig is missing
     */
    var AbstractFilterTranslator = function AbstractFilterTranslatorToExpression(filterConfig) {
        if (!filterConfig) {
            throw new TypeError('`filterConfig` is required for `FilterTranslatorToExpression`');
        }
        this.filterConfig = filterConfig;
    };

    Object.assign(AbstractFilterTranslator.prototype, {
        constructor: AbstractFilterTranslator,
        /**
         * The filter type that has to be matched to `filterConfig.type`
         * (has to be defined in descendant FilterTranslatorToExpression)
         * @type {string}
         */
        filterType: void 0,

        /**
         * Map object of possible filter criteria value to expression's operation
         * (can be defined in descendant FilterTranslatorToExpression)
         * @type {Object}
         */
        operatorMap: null,

        /**
         * Character that is used in filter value to input plural values in text field
         * @type {string}
         */
        valuesSeparator: ',',

        /**
         * Builds filter value's part of JSON validation schema
         *
         * @return {Object}
         * @protected
         * @abstract
         */
        getFilterValueSchema: function() {
            throw new Error(
                'Method `getFilterValueSchema` has to be defined in descendant FilterTranslatorToExpression');
        },

        /**
         * Takes filterValue object and checks if it has valid structure and can be translated to AST
         *
         * @param {Object} filterValue
         * @return {boolean}
         * @protected
         */
        test: function(filterValue) {
            var schema = this.getFilterValueSchema();

            return jsonSchemaValidator.validate(schema, filterValue) &&
                this.testToOperatorMap(filterValue) &&
                this.testToConfig(filterValue);
        },

        /**
         * Check if type of filter can be mapped to operator config
         *
         * @param {Object} filterValue
         * @return {boolean}
         */
        testToOperatorMap: function(filterValue) {
            return filterValue.type in this.operatorMap;
        },

        /**
         * Check if the filter type complies to the filter config
         *
         * @param {Object} filterValue
         * @return {boolean}
         */
        testToConfig: function(filterValue) {
            return _.any(this.filterConfig.choices, {value: String(filterValue.type)});
        },

        /**
         * Takes filterValue and translates it to ExpressionLanguage AST with provided leftOperand
         *
         * @param {Node} leftOperand
         * @param {Object} filterValue
         * @return {Node|null} ExpressionLanguage AST node
         * @protected
         * @abstract
         */
        translate: function(leftOperand, filterValue) {
            throw new Error('Method `translate` has to be defined in descendant FilterTranslatorToExpression');
        },

        /**
         * Splits value to array of string values when filter supports plural values
         *
         * @param {string} value
         * @return {Array.<string>}
         * @protected
         */
        splitValues: function(value) {
            return value.split(this.valuesSeparator).map(function(item) {
                return item.trim();
            });
        }
    });

    return AbstractFilterTranslator;
});
