import AbstractConditionTranslatorToExpression from './abstract-filter-translator';
import {BinaryNode, tools} from 'oroexpressionlanguage/js/expression-language-library';

class DictionaryFilterTranslatorToExpression extends AbstractConditionTranslatorToExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'dictionary';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = {
        1: { // TYPE_IN (is any of)
            operator: 'in'
        },
        2: { // TYPE_NOT_IN (is not any of)
            operator: 'not in'
        },
        3: { // EQUAL
            operator: '='
        },
        4: { // NOT_EQUAL
            operator: '!='
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
                value: {
                    type: 'array',
                    items: {type: 'string'}
                },
                params: {type: 'object'}
            }
        };
    }

    /**
     * @inheritDoc
     */
    translate(leftOperand, filterValue) {
        const operatorParams = this.constructor.OPERATOR_MAP[filterValue.type];
        const rightOperand = tools.createArrayNode(filterValue.value);

        return new BinaryNode(operatorParams.operator, leftOperand, rightOperand);
    }
}

export default DictionaryFilterTranslatorToExpression;
