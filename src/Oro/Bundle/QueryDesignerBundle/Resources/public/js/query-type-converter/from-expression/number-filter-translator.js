import {where} from 'underscore';
import AbstractFilterTranslatorFromExpression from './abstract-filter-translator';
import NumberFilterTranslatorToExpression
    from 'oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator';
import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';

const OPERATOR_MAP_ENTRIES = Object.entries(NumberFilterTranslatorToExpression.OPERATOR_MAP)
    .map(([key, value]) => [key, Object.assign({criterion: key}, value)]);

/**
 * @inheritDoc
 */
class NumberFilterTranslatorFromExpression extends AbstractFilterTranslatorFromExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'number';

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
        return node instanceof ConstantNode && (
            typeof node.attrs.value === 'number' && isFinite(node.attrs.value) ||
            typeof node.attrs.value === 'string' && node.attrs.value.length > 0 && isFinite(Number(node.attrs.value))
        );
    }

    /**
     * @inheritDoc
     */
    resolveOperatorParams(node) {
        if (!(node instanceof BinaryNode)) {
            return null;
        }

        const matchedParams = where(this.constructor.OPERATOR_MAP, {operator: node.attrs.operator})
            // clone nested params objects to preserve originals untouched
            .map(params => ({...params}));

        if (matchedParams.length === 0) {
            return null;
        }

        const operatorParams = matchedParams.find(params => {
            let leftNode;
            let rightNode;

            if ( // `between` or `not between`
                params.left && params.right &&
                (leftNode = node.nodes[0]) instanceof BinaryNode &&
                (rightNode = node.nodes[1]) instanceof BinaryNode &&
                params.left.operator === leftNode.attrs.operator &&
                params.right.operator === rightNode.attrs.operator &&
                this.checkValueAST(leftNode.nodes[1]) &&
                this.checkValueAST(rightNode.nodes[1])
            ) {
                params[params.left.valueProp] = Number(leftNode.nodes[1].attrs.value);
                params[params.right.valueProp] = Number(rightNode.nodes[1].attrs.value);
            } else if ( // `is any of` or `is not any of`
                params.hasArrayValue &&
                this.checkListOperandAST(node.nodes[1], this.checkValueAST)
            ) {
                params.value = node.nodes[1].getKeyValuePairs()
                    .map(pair => Number(pair.value.attrs.value))
                    .join(',');
            } else if ( // `is empty` or `is not empty`
                this.checkValueAST(node.nodes[1]) &&
                node.nodes[1].attrs.value === 0 &&
                params.value === 0
            ) {
                params.value = void 0;
            } else if ( // number
                this.checkValueAST(node.nodes[1]) &&
                node.nodes[1].attrs.value !== 0 &&
                params.value === void 0
            ) {
                params.value = Number(node.nodes[1].attrs.value);
            } else {
                return false;
            }

            return true;
        });

        return operatorParams || null;
    }

    /**
     * @inheritDoc
     */
    resolveFieldAST(node) {
        return node.nodes[0] instanceof BinaryNode ? node.nodes[0].nodes[0] : node.nodes[0];
    }

    /**
     * @inheritDoc
     */
    translate(node, filterConfig, operatorParams) {
        const fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));
        const data = {};

        data.type = operatorParams.criterion;

        if (operatorParams.value !== void 0) {
            data.value = operatorParams.value;
        }

        if (operatorParams.value_end !== void 0) {
            data.value_end = operatorParams.value_end;
        }

        return {
            columnName: fieldId,
            criterion: {
                filter: filterConfig.name,
                data: data
            }
        };
    }
}

export default NumberFilterTranslatorFromExpression;
