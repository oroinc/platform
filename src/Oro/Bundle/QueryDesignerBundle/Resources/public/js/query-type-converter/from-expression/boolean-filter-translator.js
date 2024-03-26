import AbstractFilterTranslatorFromExpression from './abstract-filter-translator';
import BooleanFilterTranslatorToExpression from '../to-expression/boolean-filter-translator';
import {BinaryNode, ConstantNode, GetAttrNode, UnaryNode} from 'oroexpressionlanguage/js/expression-language-library';

/**
 * @inheritDoc
 */
class BooleanFilterTranslatorFromExpression extends AbstractFilterTranslatorFromExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'boolean';

    /**
     * Resolved binary operations
     * @type {Array}
     */
    static BINARY_OPERATORS = ['=', '=='];

    /**
     * Resolved unary operations
     * @type {Array}
     */
    static UNARY_OPERATORS = ['!', 'not'];

    /**
     * @inheritDoc
     */
    static VALUE_MAP = BooleanFilterTranslatorToExpression.VALUE_MAP;

    /**
     * @inheritDoc
     */
    resolveFieldAST(node) {
        if (node instanceof GetAttrNode) {
            return node;
        } else {
            return node.nodes[0];
        }
    }

    /**
     * @inheritDoc
     */
    checkOperation(filterConfig, operatorParams) {
        return filterConfig.choices.some(option => option.value === operatorParams.value);
    }

    /**
     * @inheritDoc
     */
    resolveOperatorParams(node) {
        let params = null;
        if (node instanceof BinaryNode &&
            this.constructor.BINARY_OPERATORS.indexOf(node.attrs.operator) !== -1 &&
            node.nodes[0] instanceof GetAttrNode &&
            node.nodes[1] instanceof ConstantNode &&
            typeof node.nodes[1].attrs.value === 'boolean'
        ) {
            params = {
                value: node.nodes[1].attrs.value
            };
        } else if (
            node instanceof UnaryNode &&
            this.constructor.UNARY_OPERATORS.indexOf(node.attrs.operator) !== -1
        ) {
            params = {
                value: false
            };
        } else if (node instanceof GetAttrNode) {
            params = {
                value: true
            };
        }

        if (params) {
            params.value = this.constructor.VALUE_MAP[String(params.value)];
        }

        return params;
    }

    /**
     * @inheritDoc
     */
    translate(node, filterConfig, operatorParams) {
        const fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

        return {
            columnName: fieldId,
            criterion: {
                filter: filterConfig.name,
                data: {
                    value: operatorParams.value
                }
            }
        };
    }
}

export default BooleanFilterTranslatorFromExpression;
