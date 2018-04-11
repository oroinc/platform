define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var tools = ExpressionLanguageLibrary.tools;

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
        translate: function(leftOperand, filterValue) {
            filterValue = this.normalizeFilterValue(filterValue);
            var params = this.operatorMap[filterValue.type];
            var result;

            if (params.left && params.right) {
                result = new BinaryNode(
                    params.operator,
                    this.translateSingleValue(params.left, leftOperand, filterValue),
                    this.translateSingleValue(params.right, leftOperand, filterValue)
                );
            } else {
                result = this.translateSingleValue(params, leftOperand, filterValue);
            }

            return result;
        },

        /**
         * Translates single value to AST
         *
         * @param {Object} params
         * @param {Node} leftOperand
         * @param {Object} filterValue
         * @return {BinaryNode}
         * @protected
         */
        translateSingleValue: function(params, leftOperand, filterValue) {
            var rightOperand;
            var value = filterValue[params.valueProp || 'value'];

            if (params.hasArrayValue) {
                rightOperand = tools.createArrayNode(value);
            } else {
                if (_.has(params, 'value')) {
                    value = params.value;
                }

                rightOperand = new ConstantNode(value);
            }

            return new BinaryNode(params.operator, leftOperand, rightOperand);
        },

        /**
         * Normalizes filterValue in case it is partial value of between of notBetween filter criterion
         *
         * @param {Object} filterValue
         * @return {Object}
         */
        normalizeFilterValue: function(filterValue) {
            var type = String(filterValue.type);
            var params = this.operatorMap[type];
            var normalizedValues = _.map([filterValue.value, filterValue.value_end], function(val) {
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
                    if (!_.isUndefined(filterValue.value)) {
                        filterValue = _.defaults({
                            value: value
                        }, filterValue);
                    }

                    return filterValue;
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
                type: type,
                value: value,
                value_end: valueEnd
            }, filterValue);
        }
    });

    return NumberFilterTranslator;
});
