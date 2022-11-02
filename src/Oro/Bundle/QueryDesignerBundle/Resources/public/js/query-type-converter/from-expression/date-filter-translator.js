import _ from 'underscore';
import AbstractFilterTranslator from './abstract-filter-translator';
import DateFilterTranslatorToExpression from '../to-expression/date-filter-translator';
import {BinaryNode, ConstantNode, FunctionNode, GetAttrNode, tools}
    from 'oroexpressionlanguage/js/expression-language-library';

const {compareAST} = tools;

const OPERATOR_MAP_ENTRIES = Object.entries(DateFilterTranslatorToExpression.OPERATOR_MAP)
    .map(([key, value]) => [key, Object.assign({criterion: key}, value)]);

const PART_MAP_ENTRIES = Object.entries(DateFilterTranslatorToExpression.PART_MAP)
    .map(([key, value]) => [key, Object.assign({part: key}, value)]);

/**
 * @inheritDoc
 */
class DateFilterTranslatorFromExpression extends AbstractFilterTranslator {
    /**
     * @inheritDoc
     */
    static TYPE = 'date';

    /**
     * @inheritDoc
     */
    static OPERATOR_MAP = Object.fromEntries(OPERATOR_MAP_ENTRIES);

    /**
     * Map of value part to its params
     * @type {Object}
     */
    static PART_MAP = Object.fromEntries(PART_MAP_ENTRIES);

    /**
     * @inheritDoc
     */
    resolveOperatorParams(node) {
        if (!(node instanceof BinaryNode)) {
            return null;
        }

        const matchedParams = _.where(this.constructor.OPERATOR_MAP, {operator: node.attrs.operator})
            // clone nested params objects to preserve originals untouched
            .map(params => ({...params}));

        if (matchedParams.length === 0) {
            return null;
        }

        const operatorParams = matchedParams.find(params => {
            let leftNode;
            let rightNode;
            let partParams;
            let valueParams = {};

            if (
                params.left && params.right &&
                (leftNode = node.nodes[0]) instanceof BinaryNode &&
                (rightNode = node.nodes[1]) instanceof BinaryNode &&
                params.left.operator === leftNode.attrs.operator &&
                params.right.operator === rightNode.attrs.operator &&
                // if left operands are identical
                compareAST(leftNode.nodes[0], rightNode.nodes[0]) &&
                (partParams = this.resolvePartParams(leftNode.nodes[0])) !== void 0 &&
                (valueParams.left = this.resolveValueParams(leftNode.nodes[1], partParams)) !== void 0 &&
                (valueParams.right = this.resolveValueParams(rightNode.nodes[1], partParams)) !== void 0
            ) {
                Object.assign(params, _.pick(partParams, 'part'));
                params.left = Object.assign({}, params.left, valueParams.left);
                params.right = Object.assign({}, params.right, valueParams.right);
            } else if (
                (partParams = this.resolvePartParams(node.nodes[0])) !== void 0 &&
                (valueParams = this.resolveValueParams(node.nodes[1], partParams)) !== void 0
            ) {
                Object.assign(params, _.pick(partParams, 'part'), valueParams);
            } else {
                return false;
            }

            return true;
        });

        return operatorParams || null;
    }

    /**
     * Defines date part params on base of AST node
     *
     * @return {Object|undefined}
     * @protected
     */
    resolvePartParams(node) {
        let partParams;
        if (node instanceof FunctionNode) {
            partParams = _.findWhere(this.constructor.PART_MAP, {propModifier: node.attrs.name});
        } else if (node instanceof GetAttrNode) {
            partParams = this.constructor.PART_MAP.value;
        }
        return partParams;
    }

    /**
     * Defines date value params on base of AST node
     *
     * @return {{value: string}|{variable: string}|undefined}
     * @protected
     */
    resolveValueParams(node, partParams) {
        let variable;
        let valueParams;

        if ( // if value a constant code
            node instanceof ConstantNode &&
            typeof node.attrs.value === 'string' &&
            (!partParams.valuePattern || partParams.valuePattern.test(node.attrs.value))
        ) {
            valueParams = {value: node.attrs.value};
        } else if (// if value a variable
            node instanceof FunctionNode &&
            (variable = _.findKey(partParams.variables, value => value === node.attrs.name)) !== void 0
        ) {
            valueParams = {variable: variable};
        }

        return valueParams;
    }

    /**
     * @inheritDoc
     */
    resolveFieldAST(node) {
        const leftNode = node.nodes[0] instanceof BinaryNode ? node.nodes[0].nodes[0] : node.nodes[0];
        return leftNode instanceof FunctionNode ? leftNode.nodes[0].nodes[0] : leftNode;
    }

    /**
     * @inheritDoc
     */
    translate(node, filterConfig, operatorParams) {
        const value = {start: '', end: ''};
        const fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

        const assignSingleValue = params => {
            value[params.valueProp] = params.variable ? '{{' + params.variable + '}}' : params.value;
        };

        if (operatorParams.left && operatorParams.right) {
            assignSingleValue(operatorParams.left);
            assignSingleValue(operatorParams.right);
        } else {
            assignSingleValue(operatorParams);
        }

        return {
            columnName: fieldId,
            criterion: {
                filter: filterConfig.name,
                data: {
                    type: operatorParams.criterion,
                    part: operatorParams.part,
                    value: value
                }
            }
        };
    }

    /**
     * @inheritDoc
     */
    checkOperation(filterConfig, operatorParams) {
        const variables = _.compact(_.unique(_.pluck([
            operatorParams,
            operatorParams.left,
            operatorParams.right
        ], 'variable')));

        const {externalWidgetOptions} = filterConfig;

        return super.checkOperation(filterConfig, operatorParams) &&
            Object.keys(filterConfig.dateParts).indexOf(operatorParams.part) !== -1 &&
            variables.length === 0 ||
            externalWidgetOptions &&
            externalWidgetOptions.dateVars &&
            externalWidgetOptions.dateVars[operatorParams.part] &&
            _.all(variables, variable => !_.isEmpty(externalWidgetOptions.dateVars[operatorParams.part][variable]));
    }
}

export default DateFilterTranslatorFromExpression;

