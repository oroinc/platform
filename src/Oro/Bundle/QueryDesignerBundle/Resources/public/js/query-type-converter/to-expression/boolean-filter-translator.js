import _ from 'underscore';
import AbstractFilterTranslatorToExpression from './abstract-filter-translator';
import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';

class BooleanFilterTranslatorToExpression extends AbstractFilterTranslatorToExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'boolean';

    /**
     * Used in expression BinaryNode
     * @type {String}
     */
    static OPERATOR = '=';

    /**
     * Map expression value to filter value
     * @type {Object.<string, string>}
     */
    static VALUE_MAP = {
        'true': '1',
        'false': '2'
    };

    /**
     * @inheritDoc
     */
    getFilterValueSchema() {
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
    }

    /**
     * @inheritDoc
     */
    testToOperatorMap(filterValue) {
        // nothing to check
        return true;
    }

    /**
     * @inheritDoc
     */
    testToConfig(filterValue) {
        return _.any(this.filterConfig.choices, {value: filterValue.value});
    }

    /**
     * @inheritDoc
     */
    translate(leftOperand, filterValue) {
        const value = filterValue.value === this.constructor.VALUE_MAP['true'];
        const rightOperand = new ConstantNode(value);

        return new BinaryNode(this.constructor.OPERATOR, leftOperand, rightOperand);
    }
}

export default BooleanFilterTranslatorToExpression;
