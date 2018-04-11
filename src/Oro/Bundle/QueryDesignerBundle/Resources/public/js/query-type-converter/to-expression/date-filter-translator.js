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
    var DateFilterTranslator = function DateFilterTranslatorToExpression() {
        DateFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    DateFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    DateFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(DateFilterTranslator.prototype, {
        constructor: DateFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'date',

        /**
         * @inheritDoc
         */
        operatorMap: {
            1: { // between
                left: {
                    operator: '>=',
                    valueProp: 'start'
                },
                operator: 'and',
                right: {
                    operator: '<=',
                    valueProp: 'end'
                }
            },
            2: { // not between
                left: {
                    operator: '<',
                    valueProp: 'start'
                },
                operator: 'and',
                right: {
                    operator: '>',
                    valueProp: 'end'
                }
            },
            3: { // later than
                operator: '>=',
                valueProp: 'start'
            },
            4: { // earlier than
                operator: '<=',
                valueProp: 'end'
            },
            5: { // equals
                operator: '=',
                valueProp: 'start'
            },
            6: { // not equals
                operator: '!=',
                valueProp: 'end'
            }
        },

        /**
         * Mnemonics of filter value types (filter's criteria)
         * @type {Object}
         */
        filterCriterion: {
            between: '1',
            notBetween: '2',
            moreThan: '3',
            lessThan: '4',
            equal: '5',
            notEqual: '6'
        },

        /**
         * Map of value part to its params
         * @type {Object}
         */
        partMap: {
            value: {
                valuePattern: /^\d{4}-\d{2}-\d{2}$/,
                variables: {
                    1: 'now',
                    2: 'today',
                    3: 'startOfTheWeek',
                    4: 'startOfTheMonth',
                    5: 'startOfTheQuarter',
                    6: 'startOfTheYear',
                    17: 'currentMonthWithoutYear',
                    29: 'thisDayWithoutYear'
                }
            },
            dayofweek: {
                propModifier: 'dayOfWeek',
                valuePattern: /^[1-7]$/,
                variables: {
                    10: 'currentDayOfWeek'
                }
            },
            week: {
                propModifier: 'week',
                variables: {
                    11: 'currentWeek'
                }
            },
            day: {
                propModifier: 'dayOfMonth',
                variables: {
                    10: 'currentDayOfMonth'
                }
            },
            month: {
                propModifier: 'month',
                valuePattern: /^([1-9]|1[0-2])$/,
                variables: {
                    12: 'currentMonth',
                    16: 'firstMonthOfCurrentQuarter'
                }
            },
            quarter: {
                propModifier: 'quarter',
                variables: {
                    13: 'currentQuarter'
                }
            },
            dayofyear: {
                propModifier: 'dayOfYear',
                variables: {
                    10: 'currentDayOfYear',
                    15: 'firstDayOfCurrentQuarter'
                }
            },
            year: {
                propModifier: 'year',
                valuePattern: /^\d{4}$/,
                variables: {
                    14: 'currentYear'
                }
            }
        },

        /**
         * Variable value's mask
         * @type {RegExp}
         */
        variablePattern: /^{{(\d{1,2})}}$/,

        /**
         * @inheritDoc
         */
        getFilterValueSchema: function() {
            return {
                type: 'object',
                required: ['type', 'value', 'part'],
                additionalProperties: false,
                properties: {
                    type: {type: ['string', 'integer']},
                    value: {
                        type: 'object',
                        required: ['start', 'end'],
                        additionalProperties: false,
                        properties: {
                            start: {type: 'string'},
                            end: {type: 'string'}
                        }
                    },
                    part: {
                        'type': 'string',
                        'enum': _.keys(this.partMap)
                    }
                }
            };
        },

        /**
         * @inheritDoc
         */
        testToConfig: function(filterValue, config) {
            var part = filterValue.part;
            var value = filterValue.value;
            var result =
                DateFilterTranslator.__super__.testToConfig.call(this, filterValue, config) &&
                // check is filter part is available in config
                _.has(config.dateParts, part);

            if (result) {
                var varsConfig = _.result(_.result(config.externalWidgetOptions, 'dateVars'), part, {});
                var partParams = this.partMap[part];

                result =
                    // at least some of two values is not empty
                    (value.start || value.end) &&
                    _.all(value, function(singleValue) {
                        var variableMatch;
                        return singleValue === '' ||
                            // filter part has restriction for value by patter and the value matches it
                            (!partParams.valuePattern || partParams.valuePattern.test(singleValue)) ||
                            (
                                !partParams.variables ||
                                // value matches to variable mask
                                (variableMatch = singleValue.match(this.variablePattern)) !== null &&
                                // if is variable known by translator
                                variableMatch[1] in partParams.variables &&
                                // if is variable available in filter config
                                varsConfig && variableMatch[1] in varsConfig
                            );
                    }, this);
            }

            return result;
        },

        /**
         * @inheritDoc
         */
        translate: function(leftOperand, filterValue) {
            filterValue = this.normalizeCondition(filterValue);
            var result;
            var params = this.operatorMap[filterValue.type];

            if (params.left && params.right) {
                result = new BinaryNode(
                    params.operator,
                    this.translateSingleValue(params.left, leftOperand, filterValue),
                    // TODO: implement in expression language tools method to cloning of node and use clone instead
                    // the same left operand
                    this.translateSingleValue(params.right, leftOperand, filterValue)
                );
            } else {
                result = this.translateSingleValue(params, leftOperand, filterValue);
            }

            return result;
        },

        /**
         * Normalizes filterValue in case it is partial value of between of notBetween filter criterion
         *
         * @param {Object} filterValue
         * @return {Object}
         */
        normalizeCondition: function(filterValue) {
            var type = String(filterValue.type);
            var valueStart = filterValue.value.start;
            var valueEnd = filterValue.value.end;

            if (
                [this.filterCriterion.between, this.filterCriterion.notBetween].indexOf(type) === -1 ||
                valueStart && valueEnd
            ) {
                // nothing to normalize
                return filterValue;
            } else if (this.filterCriterion.between === type) {
                type = valueEnd ? this.filterCriterion.lessThan : this.filterCriterion.moreThan;
            } else if (this.filterCriterion.notBetween === type) {
                if (!valueEnd) {
                    // less than type expects end date
                    type = this.filterCriterion.lessThan;
                    valueEnd = valueStart;
                    valueStart = '';
                } else {
                    // more than type expects start date
                    type = this.filterCriterion.moreThan;
                    valueStart = valueEnd;
                    valueEnd = '';
                }
            }

            return _.defaults({
                type: type,
                value: {
                    start: valueStart,
                    end: valueEnd
                }
            }, filterValue);
        },

        /**
         * Translates condition for a single value of pair 'start' and 'end'
         *
         * @param {Object} params
         * @param {Node} leftOperand
         * @param {Object} filterValue
         * @return {BinaryNode}
         * @protected
         */
        translateSingleValue: function(params, leftOperand, filterValue) {
            var partParams = this.partMap[filterValue.part];
            var singleValue = filterValue.value[params.valueProp];
            var rightOperand;
            var variableMatch;

            if (partParams.propModifier) {
                leftOperand = tools.createFunctionNode(partParams.propModifier, [leftOperand]);
            }

            if (
                partParams.variables &&
                (variableMatch = singleValue.match(this.variablePattern)) !== null &&
                variableMatch[1] in partParams.variables
            ) {
                rightOperand = tools.createFunctionNode(partParams.variables[variableMatch[1]]);
            } else {
                rightOperand = new ConstantNode(singleValue);
            }

            return new BinaryNode(params.operator, leftOperand, rightOperand);
        }
    });

    return DateFilterTranslator;
});
