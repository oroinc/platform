define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;

    /**
     * @inheritDoc
     */
    function NumberFilterTranslator() {
        NumberFilterTranslator.__super__.constructor.apply(this, arguments);
    }

    NumberFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    NumberFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(NumberFilterTranslator.prototype, {
        constructor: NumberFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'number',

        /**
         * @inheritDoc
         */
        operatorMap: {
            1: { // TYPE_GREATER_EQUAL (equals or greater than)
                operator: '>='
            },
            2: { // TYPE_GREATER_THAN (greater than)
                operator: '>'
            },
            3: { // TYPE_EQUAL (equals)
                operator: '='
            },
            4: { // TYPE_NOT_EQUAL (not equals)
                operator: '!='
            },
            5: { // TYPE_LESS_EQUAL (equals or less than)
                operator: '<='
            },
            6: { // TYPE_LESS_THAN (less than)
                operator: '<'
            },
            7: { // TYPE_BETWEEN (between)
                operator: 'and',
                hasDoubleValue: true
            },
            8: { // TYPE_NOT_BETWEEN (not between)
                operator: 'and',
                hasDoubleValue: true
            },
            9: { // TYPE_IN (is any of)
                operator: 'in',
                isRange: true
            },
            10: { // TYPE_NOT_IN (is not any of)
                operator: 'not in',
                isRange: true
            },
            filter_empty_option: { // TYPE_EMPTY (is empty)
                operator: '=',
                value: 0
            },
            filter_not_empty_option: { // TYPE_NOT_EMPTY (is not empty)
                operator: '!=',
                value: 0
            }
        },

        /**
         * @inheritDoc
         */
        getFilterValueSchema: function() {
            return {
                type: 'object',
                required: ['type'],
                properties: {
                    type: {
                        type: 'string'
                    },
                    value: {
                        type: ['number', 'string']
                    },
                    value_end: {
                        type: ['number', 'string']
                    }
                }
            };
        },

        /**
         * @inheritDoc
         * @return {Array.<number>}
         */
        splitValues: function(values) {
            var array = NumberFilterTranslator.__super__.splitValues.apply(this, arguments);

            return _.map(array, function(item) {
                return parseInt(item, 10);
            });
        },

        /**
         * @inheritDoc
         */
        translate: function(condition) {
            var operand;
            var value = condition.criterion.data.value;
            var params = this.operatorMap[condition.criterion.data.type];

            if (params.isRange) {
                operand = new ArrayNode();
                this.splitValues(value).forEach(function(val) {
                    operand.addElement(new ConstantNode(val));
                });

            // } else if (params.hasDoubleValue) {
            // TODO: implement in BAP-16713
            } else if (_.has(params, 'value')) {
                operand = new ConstantNode(params.value);
            } else {
                operand = new ConstantNode(value);
            }

            return new BinaryNode(
                params.operator,
                this.fieldIdTranslator.translate(condition.columnName),
                operand
            );
        }
    });

    return NumberFilterTranslator;
});
