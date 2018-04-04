define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var _ = require('underscore');

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
                left: {
                    operator: '>=',
                    valueProp: 'value'
                },
                operator: 'and',
                right: {
                    operator: '<=',
                    valueProp: 'value_end'
                }
            },
            8: { // TYPE_NOT_BETWEEN (not between)
                left: {
                    operator: '<',
                    valueProp: 'value'
                },
                operator: 'and',
                right: {
                    operator: '>',
                    valueProp: 'value_end'
                }
            },
            9: { // TYPE_IN (is any of)
                operator: 'in',
                hasArrayValue: true
            },
            10: { // TYPE_NOT_IN (is not any of)
                operator: 'not in',
                hasArrayValue: true
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
         * Mnemonics of filter value types (filter's criteria)
         * @type {Object}
         */
        filterCriterion: {
            equalOrMoreThan: '1',
            moreThan: '2',
            equal: '3',
            notEqual: '4',
            equalOrLessThan: '5',
            lessThan: '6',
            between: '7',
            notBetween: '8',
            anyOf: '9',
            notAnyOf: '10',
            empty: 'filter_empty_option',
            notEmpty: 'filter_not_empty_option'
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
                return Number(item);
            });
        },

        /**
         * @inheritDoc
         */
        translate: function(condition) {
            condition = this.normalizeCondition(condition);
            var params = this.operatorMap[condition.criterion.data.type];
            var result;

            if (params.left && params.right) {
                result = new BinaryNode(
                    params.operator,
                    this.translateSingleValue(params.left, condition),
                    this.translateSingleValue(params.right, condition)
                );
            } else {
                result = this.translateSingleValue(params, condition);
            }

            return result;
        },

        /**
         * Translates condition for a single value
         *
         * @param {Object} params
         * @param {Object} condition
         * @return {BinaryNode}
         * @protected
         */
        translateSingleValue: function(params, condition) {
            var rightOperand;
            var value = condition.criterion.data[params.valueProp || 'value'];

            if (params.hasArrayValue) {
                rightOperand = new ArrayNode();

                _.each(value, function(val) {
                    rightOperand.addElement(new ConstantNode(val));
                });
            } else {
                if (_.has(params, 'value')) {
                    value = params.value;
                }

                rightOperand = new ConstantNode(value);
            }

            return new BinaryNode(
                params.operator,
                this.fieldIdTranslator.translate(condition.columnName),
                rightOperand
            );
        },

        /**
         * Normalizes conditions in case it is partial value of between of notBetween filter criterion
         *
         * @param {Object} condition
         * @return {Object}
         */
        normalizeCondition: function(condition) {
            var data = condition.criterion.data;
            var type = String(data.type);
            var params = this.operatorMap[type];
            var normalizedValues = _.map([data.value, data.value_end], function(val) {
                if (!_.isUndefined(val)) {
                    if (params.hasArrayValue) {
                        return this.splitValues(val);
                    // skip empty value
                    } else if (_.isString(val) && !_.isEmpty(_.trim(val))) {
                        return Number(val);
                    } else {
                        return val;
                    }
                }
            }, this);
            var value = normalizedValues[0];
            var valueEnd = normalizedValues[1];

            if (
                [this.filterCriterion.between, this.filterCriterion.notBetween].indexOf(type) === -1 ||
                _.isNumber(value) && _.isNumber(valueEnd)
            ) {
                // when valueEnd is lower than value
                if (valueEnd < value) {
                    var _valueEnd = valueEnd;

                    valueEnd = value;
                    value = _valueEnd;
                } else {
                    if (!_.isUndefined(data.value)) {
                        condition.criterion.data.value = value;
                    }

                    return condition;
                }
            } else if (!_.isUndefined(value) && !_.isUndefined(valueEnd)) {
                if (valueEnd) {
                    type = type === this.filterCriterion.between
                        ? this.filterCriterion.moreThan
                        : this.filterCriterion.lessThan;

                    value = valueEnd;
                    valueEnd = 0;
                } else {
                    type = type === this.filterCriterion.between
                        ? this.filterCriterion.lessThan
                        : this.filterCriterion.moreThan;
                }
            }

            return _.defaults({
                criterion: _.defaults({
                    data: _.defaults({
                        type: type,
                        value: value,
                        value_end: valueEnd
                    }, condition.criterion.data)
                }, condition.criterion)
            }, condition);
        }
    });

    return NumberFilterTranslator;
});
