define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var _ = require('underscore');

    /**
     * @inheritDoc
     */
    function BooleanFilterTranslator() {
        BooleanFilterTranslator.__super__.constructor.apply(this, arguments);
    }

    BooleanFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    BooleanFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(BooleanFilterTranslator.prototype, {
        constructor: BooleanFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'boolean',

        /**
         * @inheritDoc
         */
        operator: '==',

        /**
         * @inheritDoc
         */
        getFilterValueSchema: function() {
            return {
                type: 'object',
                required: ['value'],
                properties: {
                    value: {
                        type: 'string'
                    }
                }
            };
        },

        /**
         * @inheritDoc
         */
        test: function(condition) {
            var filterConfigs = this.filterConfigProvider.getFilterConfigsByType(this.filterType);
            var schema = this.getConditionSchema(filterConfigs);

            return jsonSchemaValidator.validate(schema, condition);
        },

        /**
         * @inheritDoc
         */
        translate: function(condition) {
            var value = this.convertToBoolean(condition.criterion.data.value);

            return new BinaryNode(
                this.operator,
                this.fieldIdTranslator.translate(condition.columnName),
                new ConstantNode(value)
            );
        },

        /**
         * Convert criterion value to boolean
         * @param {string} value
         * @returns {boolean}
         */
        convertToBoolean: function(value) {
            return _.isEqual(value, '1');
        }
    });

    return BooleanFilterTranslator;
});
