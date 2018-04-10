define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createFunctionNode = ExpressionLanguageLibrary.tools.createFunctionNode;

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
        testToConfig: function(condition, config) {
            var part = condition.criterion.data.part;
            var value = condition.criterion.data.value;
            var result =
                DateFilterTranslator.__super__.testToConfig.call(this, condition, config) &&
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
        translate: function(condition) {
            condition = this.normalizeCondition(condition);
            var result;
            var params = this.operatorMap[condition.criterion.data.type];

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
         * Normalizes conditions in case it is partial value of between of notBetween filter criterion
         *
         * @param {Object} condition
         * @return {Object}
         */
        normalizeCondition: function(condition) {
            var type = String(condition.criterion.data.type);
            var valueStart = condition.criterion.data.value.start;
            var valueEnd = condition.criterion.data.value.end;

            if (
                [this.filterCriterion.between, this.filterCriterion.notBetween].indexOf(type) === -1 ||
                valueStart && valueEnd
            ) {
                // nothing to normalize
                return condition;
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
                criterion: _.defaults({
                    data: _.defaults({
                        type: type,
                        value: {
                            start: valueStart,
                            end: valueEnd
                        }
                    }, condition.criterion.data)
                }, condition.criterion)
            }, condition);
        },

        /**
         * Translates condition for a single value of pair 'start' and 'end'
         *
         * @param {Object} params
         * @param {Object} condition
         * @return {BinaryNode}
         * @protected
         */
        translateSingleValue: function(params, condition) {
            var partParams = this.partMap[condition.criterion.data.part];
            var leftOperandAST = this.fieldIdTranslator.translate(condition.columnName);
            var singleValue = condition.criterion.data.value[params.valueProp];
            var rightOperandAST;
            var variableMatch;

            if (partParams.propModifier) {
                leftOperandAST = createFunctionNode(partParams.propModifier, [leftOperandAST]);
            }

            if (
                partParams.variables &&
                (variableMatch = singleValue.match(this.variablePattern)) !== null &&
                variableMatch[1] in partParams.variables
            ) {
                rightOperandAST = createFunctionNode(partParams.variables[variableMatch[1]]);
            } else {
                rightOperandAST = new ConstantNode(singleValue);
            }

            return new BinaryNode(params.operator, leftOperandAST, rightOperandAST);
        }
    });

    return DateFilterTranslator;
});
