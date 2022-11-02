import _ from 'underscore';
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
     * Converts expression to expression string
     *
     * @param {Array<Object|Array|string>} condition
     * @return {string|undefined}
     */
    convert(condition) {
        if (!this.test(condition)) {
            return void 0;
        } else if (condition.length === 0) {
            return '';
        }

        const ast = this.convertToAST(condition);

        return ast ? this.compiler.compile(ast) : void 0;
    }

    /**
     * Check order and type of elements in condition structure
     *
     * @param {Array<Object|Array|string>} condition
     * @return {boolean}
     */
    test(condition) {
        return _.isArray(condition) &&
            // empty or has odd length
            (condition.length === 0 || condition.length % 2 === 1) &&
            condition.every((item, index) => {
                const isOdd = index % 2 === 1;
                // every element with odd index has to be string 'AND' or 'OR'
                return isOdd && (item === 'AND' || item === 'OR') ||
                    // every element with even index has to be not empty
                    !isOdd && !_.isEmpty(item) && (
                        // array with valid structure
                        _.isArray(item) && this.test(item) ||
                        // or plain object
                        _.isObject(item) && !_.isArray(item)
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
            let ast;
            if (_.isArray(item)) {
                return this.convertToAST(item);
            } else if (_.isObject(item)) {
                for (let i = 0; i < this.translators.length; i++) {
                    ast = this.translators[i].tryToTranslate(item);
                    if (ast) {
                        break;
                    }
                }
                return ast;
            } else {
                return item.toLowerCase();
            }
        });

        if (!_.every(mapped)) {
            return null;
        }

        let ast = mapped[0];
        for (let i = 1; i < mapped.length; i += 2) {
            ast = new BinaryNode(mapped[i], ast, mapped[i + 1]);
        }

        return ast;
    }
}

export default QueryConditionConverterToExpression;
