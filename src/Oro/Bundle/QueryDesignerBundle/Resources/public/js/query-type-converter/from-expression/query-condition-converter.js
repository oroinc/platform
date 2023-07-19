import {BinaryNode} from 'oroexpressionlanguage/js/expression-language-library';

class QueryConditionConverterFromExpression {
    constructor(conditionTranslators) {
        if (!conditionTranslators) {
            throw new TypeError(
                '`conditionTranslators` are required for `QueryConditionConverterFromExpression`');
        }

        this.translators = conditionTranslators;
    }

    /**
     * Converts parsed expression to conditions array
     *
     * @param {ParsedExpression} parsedExpression
     * @return {Array<Object|Array|string>|null}
     */
    convert(parsedExpression) {
        return this.convertToCondition(parsedExpression.nodes);
    }

    /**
     * Takes AST and converts it to condition structure
     *
     * @param {Node|null} node
     * @return {Array<Object|Array|string>|null}
     * @protected
     */
    convertToCondition(node) {
        const conditions = [];

        let condition;
        for (let i = 0; i < this.translators.length; i++) {
            condition = this.translators[i].tryToTranslate(node);
            if (condition) {
                break;
            }
        }

        if (condition) {
            conditions.push(condition);
        } else if (node instanceof BinaryNode && ['and', 'or'].indexOf(node.attrs.operator) !== -1) {
            const {operator} = node.attrs;
            const [leftNode, rightNode] = node.nodes;
            const leftCondition = this.convertToCondition(leftNode);
            const rightCondition = this.convertToCondition(rightNode);

            if (leftCondition && rightCondition) {
                const isConditionGroup = (operator, node) => {
                    // if OR operation is deeper in AST with following AND operation,
                    // the condition needs to be wrapped into a group
                    return operator === 'and' && node instanceof BinaryNode && node.attrs.operator === 'or';
                };

                conditions.push(
                    ...(isConditionGroup(operator, leftNode) ? [leftCondition] : leftCondition),
                    operator.toUpperCase(),
                    ...(isConditionGroup(operator, rightNode) ? [rightCondition] : rightCondition)
                );
            }
        }

        return conditions.length ? conditions : null;
    }
}

export default QueryConditionConverterFromExpression;
