define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
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
        testToOperatorMap: function(filterValue) {
            return !_.has(filterValue, 'type');
        },

        /**
         * @inheritDoc
         */
        testToConfig: function(filterValue, config) {
            return _.any(config.choices, {value: filterValue.value});
        },

        /**
         * @inheritDoc
         */
        translate: function(leftOperand, filterValue) {
            var value = this.convertToBoolean(filterValue.value);

            return new BinaryNode(
                this.operator,
                leftOperand,
                new ConstantNode(value)
            );
        },

        /**
         * Convert criterion value to boolean
         *
         * @param {string} value
         * @returns {boolean}
         */
        convertToBoolean: function(value) {
            return _.isEqual(value, '1');
        }
    });

    return BooleanFilterTranslator;
});
