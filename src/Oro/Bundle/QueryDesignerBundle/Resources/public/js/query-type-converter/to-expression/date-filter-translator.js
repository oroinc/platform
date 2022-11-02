import _ from 'underscore';
import AbstractConditionTranslatorToExpression from './abstract-filter-translator';
import {BinaryNode, ConstantNode, tools} from 'oroexpressionlanguage/js/expression-language-library';

class DateFilterTranslatorToExpression extends AbstractConditionTranslatorToExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'date';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = {
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
    };

    /**
     * Mnemonics of filter value types (filter's criteria)
     * @type {Object}
     */
    static FILTER_CRITERION = {
        between: '1',
        notBetween: '2',
        moreThan: '3',
        lessThan: '4',
        equal: '5',
        notEqual: '6'
    };

    /**
     * Map of value part to its params
     * @type {Object}
     */
    static PART_MAP = {
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
    };

    /**
     * Variable value's mask
     * @type {RegExp}
     */
    static VARIABLE_PATTERN = /^{{(\d{1,2})}}$/;

    /**
     * @inheritDoc
     */
    getFilterValueSchema() {
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
                    'enum': Object.keys(this.constructor.PART_MAP)
                }
            }
        };
    }

    /**
     * @inheritDoc
     */
    testToConfig(filterValue) {
        const {part, value} = filterValue;
        let result =
            super.testToConfig(filterValue) &&
            // check is filter part is available in config
            _.has(this.filterConfig.dateParts, part);

        if (result) {
            const varsConfig = _.result(_.result(this.filterConfig.externalWidgetOptions, 'dateVars'), part, {});
            const partParams = this.constructor.PART_MAP[part];

            result =
                // at least some of two values is not empty
                (value.start || value.end) &&
                _.all(value, singleValue => {
                    let variableMatch;
                    return singleValue === '' ||
                        // filter part has restriction for value by patter and the value matches it
                        (!partParams.valuePattern || partParams.valuePattern.test(singleValue)) ||
                        (
                            !partParams.variables ||
                            // value matches to variable mask
                            (variableMatch = singleValue.match(this.constructor.VARIABLE_PATTERN)) !== null &&
                            // if is variable known by translator
                            variableMatch[1] in partParams.variables &&
                            // if is variable available in filter config
                            varsConfig && variableMatch[1] in varsConfig
                        );
                });
        }

        return result;
    }

    /**
     * @inheritDoc
     */
    translate(leftOperand, filterValue) {
        filterValue = this.normalizeFilterValue(filterValue);
        let result;
        const operatorParams = this.constructor.OPERATOR_MAP[filterValue.type];

        if (operatorParams.left && operatorParams.right) {
            result = new BinaryNode(
                operatorParams.operator,
                this.translateSingleValue(leftOperand, filterValue, operatorParams.left),
                this.translateSingleValue(tools.cloneAST(leftOperand), filterValue, operatorParams.right)
            );
        } else {
            result = this.translateSingleValue(leftOperand, filterValue, operatorParams);
        }

        return result;
    }

    /**
     * Normalizes filterValue in case it is partial value of between of notBetween filter criterion
     *
     * @param {Object} filterValue
     * @return {Object}
     */
    normalizeFilterValue(filterValue) {
        let type = String(filterValue.type);
        let {start: valueStart, end: valueEnd} = filterValue.value;
        const FILTER_CRITERION = this.constructor.FILTER_CRITERION;

        if (
            [FILTER_CRITERION.between, FILTER_CRITERION.notBetween].indexOf(type) === -1 ||
            valueStart && valueEnd
        ) {
            // nothing to normalize
            return filterValue;
        } else if (FILTER_CRITERION.between === type) {
            type = valueEnd ? FILTER_CRITERION.lessThan : FILTER_CRITERION.moreThan;
        } else if (FILTER_CRITERION.notBetween === type) {
            if (!valueEnd) {
                // less than type expects end date
                type = FILTER_CRITERION.lessThan;
                valueEnd = valueStart;
                valueStart = '';
            } else {
                // more than type expects start date
                type = FILTER_CRITERION.moreThan;
                valueStart = valueEnd;
                valueEnd = '';
            }
        }

        return {
            ...filterValue,
            type,
            value: {
                start: valueStart,
                end: valueEnd
            }
        };
    };

    /**
     * Translates a single part of pair 'start' and 'end' value of filter
     *
     * @param {Node} leftOperand
     * @param {Object} filterValue
     * @param {Object} operatorParams
     * @return {BinaryNode}
     * @protected
     */
    translateSingleValue(leftOperand, filterValue, operatorParams) {
        const partParams = this.constructor.PART_MAP[filterValue.part];
        const singleValue = filterValue.value[operatorParams.valueProp];
        let rightOperand;
        let variableMatch;

        if (partParams.propModifier) {
            leftOperand = tools.createFunctionNode(partParams.propModifier, [leftOperand]);
        }

        if (
            partParams.variables &&
            (variableMatch = singleValue.match(this.constructor.VARIABLE_PATTERN)) !== null &&
            variableMatch[1] in partParams.variables
        ) {
            rightOperand = tools.createFunctionNode(partParams.variables[variableMatch[1]]);
        } else {
            rightOperand = new ConstantNode(singleValue);
        }

        return new BinaryNode(operatorParams.operator, leftOperand, rightOperand);
    }
}

export default DateFilterTranslatorToExpression;
