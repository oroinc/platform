define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;

    /**
     * @inheritDoc
     */
    var BooleanFilterTranslator = function BooleanFilterTranslatorToExpression() {
        BooleanFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    BooleanFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    BooleanFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(BooleanFilterTranslator.prototype, {
        constructor: BooleanFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'boolean',

        /**
         * Used in expression BinaryNode
         * @type {String}
         */
        operator: '=',

        /**
         * Map expression value to filter value
         * @type {Object.<string, string>}
         */
        valueMap: {
            'true': '1',
            'false': '2'
        },

        /**
         * @inheritDoc
         */
        getFilterValueSchema: function() {
            return {
                type: 'object',
                required: ['value'],
                additionalProperties: false,
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
            // nothing to check
            return true;
        },

        /**
         * @inheritDoc
         */
        testToConfig: function(filterValue) {
            return _.any(this.filterConfig.choices, {value: filterValue.value});
        },

        /**
         * @inheritDoc
         */
        translate: function(leftOperand, filterValue) {
            var value = filterValue.value === this.valueMap['true'];
            var rightOperand = new ConstantNode(value);

            return new BinaryNode(this.operator, leftOperand, rightOperand);
        }
    });

    return BooleanFilterTranslator;
});
