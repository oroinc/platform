import AbstractFilterTranslatorToExpression from './abstract-filter-translator';
import {BinaryNode, ConstantNode, tools} from 'oroexpressionlanguage/js/expression-language-library';

class StringFilterTranslatorToExpression extends AbstractFilterTranslatorToExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'string';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = {
        1: { // contains
            operator: 'matches',
            valueModifier: 'containsRegExp'
        },
        2: { // does not contain
            operator: 'not matches',
            valueModifier: 'containsRegExp'
        },
        3: { // is equal to
            operator: '='
        },
        4: { // starts with
            operator: 'matches',
            valueModifier: 'startWithRegExp'
        },
        5: { // ends with
            operator: 'matches',
            valueModifier: 'endWithRegExp'
        },
        6: { // is any of
            operator: 'in',
            hasArrayValue: true
        },
        7: { // is not any of
            operator: 'not in',
            hasArrayValue: true
        },
        filter_empty_option: { // is empty
            operator: '=',
            value: ''
        },
        filter_not_empty_option: { // is not empty
            operator: '!=',
            value: ''
        }
    };

    /**
     * @inheritDoc
     */
    getFilterValueSchema() {
        return {
            type: 'object',
            required: ['type', 'value'],
            properties: {
                type: {type: 'string'},
                value: {type: 'string'}
            }
        };
    }

    /**
     * @inheritDoc
     */
    translate(leftOperand, filterValue) {
        let rightOperand;
        const value = filterValue.value;
        const operatorParams = this.constructor.OPERATOR_MAP[filterValue.type];

        if (operatorParams.hasArrayValue) {
            rightOperand = tools.createArrayNode(this.splitValues(value));
        } else if (operatorParams.valueModifier) {
            rightOperand = tools.createFunctionNode(operatorParams.valueModifier, [value]);
        } else if ('value' in operatorParams) {
            rightOperand = new ConstantNode(operatorParams.value);
        } else {
            rightOperand = new ConstantNode(filterValue.value);
        }

        return new BinaryNode(operatorParams.operator, leftOperand, rightOperand);
    }
}

export default StringFilterTranslatorToExpression;
