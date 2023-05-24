import {findKey} from 'underscore';
import AbstractFilterTranslatorFromExpression from './abstract-filter-translator';
import DictionaryFilterTranslatorToExpression from '../to-expression/dictionary-filter-translator';
import {BinaryNode, ConstantNode, GetAttrNode} from 'oroexpressionlanguage/js/expression-language-library';

/**
 * @inheritDoc
 */
class DictionaryFilterTranslatorFromExpression extends AbstractFilterTranslatorFromExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'dictionary';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = DictionaryFilterTranslatorToExpression.OPERATOR_MAP;

    /**
     * Checks if node has correct type and value
     *
     * @param {Node} node
     * @return {boolean}
     * @protected
     */
    checkValueAST(node) {
        return node instanceof ConstantNode && (
            typeof node.attrs.value === 'string' ||
            typeof node.attrs.value === 'number' && isFinite(node.attrs.value)
        );
    }

    /**
     * @inheritDoc
     */
    resolveOperatorParams(node) {
        if (
            node instanceof BinaryNode &&
            node.nodes[0] instanceof GetAttrNode &&
            this.checkListOperandAST(node.nodes[1], this.checkValueAST)
        ) {
            const criterion = findKey(this.constructor.OPERATOR_MAP, operatorParams => {
                return operatorParams.operator === node.attrs.operator;
            });

            if (criterion) {
                return {criterion: criterion, operator: node.attrs.operator};
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    translate(node, filterConfig, operatorParams) {
        const fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

        const condition = {
            columnName: fieldId,
            criterion: {
                filter: filterConfig.name,
                data: {
                    type: operatorParams.criterion,
                    value: node.nodes[1].getKeyValuePairs().map(pair => String(pair.value.attrs.value))
                }
            }
        };

        if (filterConfig.filterParams) {
            condition.criterion.data.params = filterConfig.filterParams;
        }

        return condition;
    }
}

export default DictionaryFilterTranslatorFromExpression;
