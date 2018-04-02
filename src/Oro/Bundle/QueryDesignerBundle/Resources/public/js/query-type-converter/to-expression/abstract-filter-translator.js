define(function(require) {
    'use strict';

    var _ = require('underscore');
    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');
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
         * Character that is used in filter value to input plural values in text field
         * @type {string}
         */
        valuesSeparator: ',',

        /**
         * Map object of possible filter criteria value to expression's operation
         * (can to be defined in descendant FilterTranslatorToExpression)
         * @type {Object}
         */
        operatorMap: null,

        /**
         * The filter type that has to be matched to `filterConfig.type`
         * (has to be defined in descendant FilterTranslatorToExpression)
         * @type {string}
         */
        filterType: void 0,

        /**
         * Builds JSON validation schema taking in account filter configuration
         *
         * @param {Object} filterConfigs
         * @return {Object}
         * @protected
         */
        getConditionSchema: function(filterConfigs) {
            return {
                type: 'object',
                required: ['columnName', 'criterion'],
                properties: {
                    columnName: {type: 'string'},
                    criterion: {
                        type: 'object',
                        required: ['data', 'filter'],
                        properties: {
                            filter: {
                                'type': 'string',
                                'enum': _.pluck(filterConfigs, 'name')
                            },
                            data: this.getFilterValueSchema(filterConfigs)
                        }
                    }
                }
            };
        },

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
         * Takes condition object and checks if it has valid structure and can be translated to AST
         *
         * @param {Object} condition
         * @return {boolean}
         * @protected
         */
        test: function(condition) {
            if (!(condition.criterion.data.type in this.operatorMap)) {
                return false;
            }
            var result = false;
            var filterConfigs = this.filterConfigProvider.getFilterConfigsByType(this.filterType);
            var schema = this.getConditionSchema(filterConfigs);

            if (filterConfigs && jsonSchemaValidator.validate(schema, condition)) {
                var config = _.findWhere(filterConfigs, {name: condition.criterion.filter});
                result = config && this.testToConfig(condition, config);
            }

            return result;
        },

        /**
         * Check if the condition complies to the filter config
         *
         * @param {Object} condition
         * @param {Object} config
         * @return {boolean}
         */
        testToConfig: function(condition, config) {
            return _.pluck(config.choices, 'value').indexOf(condition.criterion.data.type) !== -1;
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
