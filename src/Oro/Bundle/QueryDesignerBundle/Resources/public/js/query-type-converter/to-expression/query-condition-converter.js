import {isEmpty, isObject} from 'underscore';
import ToExpressionCompiler from 'oroexpressionlanguage/js/to-expression-compiler';
import {BinaryNode} from 'oroexpressionlanguage/js/expression-language-library';

class QueryConditionConverterToExpression {
    constructor(conditionTranslators) {
        if (!conditionTranslators) {
            throw new TypeError(
                '`conditionTranslators` are required for `QueryConditionConverterToExpression`');
        }
        this.translators = conditionTranslators;
        this.compiler = new ToExpressionCompiler();
    }

    /**
     * Converts conditions array to expression string
     *
     * @param {Array<Object|Array|string>} condition
     * @return {string|undefined}
     */
    convert(condition) {
        if (!this.test(condition)) {
            return null;
        } else if (condition.length === 0) {
            return '';
        }

        const ast = this.convertToAST(condition);

        return ast ? this.compiler.compile(ast) : null;
    }

    /**
     * Check order and type of elements in condition structure
     *
     * @param {Array<Object|Array|string>} condition
     * @return {boolean}
     */
    test(condition) {
        return Array.isArray(condition) &&
            // empty or has odd length
            (condition.length === 0 || condition.length % 2 === 1) &&
            condition.every((item, index) => {
                const isOdd = index % 2 === 1;
                // every element with odd index has to be string 'AND' or 'OR'
                return isOdd && (item === 'AND' || item === 'OR') ||
                    // every element with even index has to be not empty
                    !isOdd && !isEmpty(item) && (
                        // array with valid structure
                        Array.isArray(item) && this.test(item) ||
                        // or plain object
                        isObject(item) && !Array.isArray(item)
                    );
            });
    }

    /**
     * Takes valid condition structure and converts it to AST
     *
     * @param {Array<Object|Array|string>} condition
     * @return {Node|null}
     */
    convertToAST(condition) {
        const mapped = condition.map(item => {
            let node;
            if (Array.isArray(item)) {
                return this.convertToAST(item);
            } else if (isObject(item)) {
                for (let i = 0; i < this.translators.length; i++) {
                    node = this.translators[i].tryToTranslate(item);
                    if (node) {
                        break;
                    }
                }
                return node;
            } else {
                return item.toLowerCase();
            }
        });

        if (mapped.some(item => isEmpty(item))) {
            return null;
        }

        let index;
        // first process all AND operations, since it has precedence over OR operation
        while ((index = mapped.indexOf('and')) !== -1) {
            const [leftNode, operation, rightNode] = mapped.slice(index - 1, index + 2);
            const node = new BinaryNode(operation, leftNode, rightNode);
            mapped.splice(index - 1, 3, node);
        }

        // combine remaining operations, which are all OR operations
        while (mapped.length > 1) {
            const [leftNode, operation, rightNode] = mapped.slice(0, 3);
            const node = new BinaryNode(operation, leftNode, rightNode);
            mapped.splice(0, 3, node);
        }

        return mapped[0];
    }
}

export default QueryConditionConverterToExpression;
