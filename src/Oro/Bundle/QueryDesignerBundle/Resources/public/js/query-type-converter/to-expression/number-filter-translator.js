import _ from 'underscore';
import AbstractFilterTranslatorToExpression from './abstract-filter-translator';
import {BinaryNode, ConstantNode, tools} from 'oroexpressionlanguage/js/expression-language-library';

/**
 * @inheritDoc
 */
class NumberFilterTranslatorToExpression extends AbstractFilterTranslatorToExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'number';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = {
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
    };

    /**
     * Mnemonics of filter value types (filter's criteria)
     * @type {Object}
     */
    static FILTER_CRITERION = {
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
    };

    /**
     * @inheritDoc
     */
    getFilterValueSchema() {
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
    }

    /**
     * @inheritDoc
     * @return {Array.<number>}
     */
    splitValues(values) {
        return super.splitValues(values).map(item => Number(item));
    }

    /**
     * @inheritDoc
     */
    translate(leftOperand, filterValue) {
        filterValue = this.normalizeFilterValue(filterValue);
        const operatorParams = this.constructor.OPERATOR_MAP[filterValue.type];
        let result;

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
     * Translates single value to AST
     *
     * @param {Node} leftOperand
     * @param {Object} filterValue
     * @param {Object} operatorParams
     * @return {BinaryNode}
     * @protected
     */
    translateSingleValue(leftOperand, filterValue, operatorParams) {
        let rightOperand;
        let value = filterValue[operatorParams.valueProp || 'value'];

        if (operatorParams.hasArrayValue) {
            rightOperand = tools.createArrayNode(value);
        } else {
            if (_.has(operatorParams, 'value')) {
                value = operatorParams.value;
            }

            rightOperand = new ConstantNode(value);
        }

        return new BinaryNode(operatorParams.operator, leftOperand, rightOperand);
    }

    /**
     * Normalizes filterValue in case it is partial value of between of notBetween filter criterion
     *
     * @param {Object} filterValue
     * @return {Object}
     */
    normalizeFilterValue(filterValue) {
        let type = String(filterValue.type);
        const FILTER_CRITERION = this.constructor.FILTER_CRITERION;
        const operatorParams = this.constructor.OPERATOR_MAP[type];
        let [value, valueEnd] = [filterValue.value, filterValue.value_end].map(value => {
            if (!_.isUndefined(value)) {
                if (operatorParams.hasArrayValue) {
                    return this.splitValues(value);
                // skip empty value
                } else if (typeof value === 'string' && value.trim()) {
                    return Number(value);
                } else {
                    return value;
                }
            }
        });

        if (
            [FILTER_CRITERION.between, FILTER_CRITERION.notBetween].indexOf(type) === -1 ||
            _.isNumber(value) && _.isNumber(valueEnd)
        ) {
            // when valueEnd is lower than value
            if (valueEnd < value) {
                const _valueEnd = valueEnd;

                valueEnd = value;
                value = _valueEnd;
            } else {
                if (!_.isUndefined(filterValue.value)) {
                    filterValue = {
                        ...filterValue,
                        value
                    };
                }

                return filterValue;
            }
        } else if (!_.isUndefined(value) && !_.isUndefined(valueEnd)) {
            if (valueEnd) {
                type = type === FILTER_CRITERION.between ? FILTER_CRITERION.moreThan : FILTER_CRITERION.lessThan;

                value = valueEnd;
                valueEnd = 0;
            } else {
                type = type === FILTER_CRITERION.between ? FILTER_CRITERION.lessThan : FILTER_CRITERION.moreThan;
            }
        }

        return {
            ...filterValue,
            type,
            value,
            value_end: valueEnd
        };
    }
}

export default NumberFilterTranslatorToExpression;
