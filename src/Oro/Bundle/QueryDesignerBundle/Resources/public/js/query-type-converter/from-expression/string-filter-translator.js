import {findWhere, where} from 'underscore';
import AbstractFilterTranslatorFromExpression from './abstract-filter-translator';
import StringFilterTranslatorToExpression from '../to-expression/string-filter-translator';
import {BinaryNode, ConstantNode, GetAttrNode, FunctionNode}
    from 'oroexpressionlanguage/js/expression-language-library';

const OPERATOR_MAP_ENTRIES = Object.entries(StringFilterTranslatorToExpression.OPERATOR_MAP)
    .map(([key, value]) => [key, Object.assign({
        criterion: key,
        hasArrayValue: false,
        valueModifier: void 0
    }, value)]);

/**
 * @inheritDoc
 */
class StringFilterTranslatorFromExpression extends AbstractFilterTranslatorFromExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'string';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = Object.fromEntries(OPERATOR_MAP_ENTRIES);

    /**
     * Checks if node has correct type and value
     *
     * @param {Node} node
     * @return {boolean}
     * @protected
     */
    checkValueAST(node) {
        return node instanceof ConstantNode && typeof node.attrs.value === 'string';
    }

    /**
     * @inheritDoc
     */
    resolveOperatorParams(node) {
        if (!(node instanceof BinaryNode && node.nodes[0] instanceof GetAttrNode)) {
            return null;
        }

        let params;
        const valueNode = node.nodes[1];
        let matchedParams = where(this.constructor.OPERATOR_MAP, {operator: node.attrs.operator});

        if (matchedParams.length === 0) {
            return null;
        }

        if (this.checkValueAST(valueNode)) {
            matchedParams = where(matchedParams, {hasArrayValue: false, valueModifier: void 0});
            if (matchedParams.length > 0) {
                params = findWhere(matchedParams, {value: valueNode.attrs.value}) || matchedParams[0];
            }
        } else if (this.checkListOperandAST(valueNode, this.checkValueAST)) {
            params = findWhere(matchedParams, {hasArrayValue: true});
        } else if (
            valueNode instanceof FunctionNode &&
            valueNode.nodes[0].nodes.length === 1 &&
            this.checkValueAST(valueNode.nodes[0].nodes[0])
        ) {
            params = findWhere(matchedParams, {valueModifier: valueNode.attrs.name});
        }

        return params || null;
    }

    /**
     * @inheritDoc
     */
    translate(node, filterConfig, operatorParams) {
        let value;
        const fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));
        const valueNode = node.nodes[1];

        if (operatorParams.valueModifier) {
            value = valueNode.nodes[0].nodes[0].attrs.value;
        } else if (operatorParams.hasArrayValue) {
            value = node.nodes[1].getKeyValuePairs().map(pair => String(pair.value.attrs.value)).join(', ');
        } else {
            value = valueNode.attrs.value;
        }

        const condition = {
            columnName: fieldId,
            criterion: {
                filter: filterConfig.name,
                data: {
                    type: operatorParams.criterion,
                    value: value
                }
            }
        };

        return condition;
    }
}

export default StringFilterTranslatorFromExpression;
