import {ArrayNode} from 'oroexpressionlanguage/js/expression-language-library';
import {isIndexedArrayNode} from 'oroexpressionlanguage/js/expression-language-tools';

/**
 * @typedef {Object} OperatorParams
 * @property {string} criterion - used for determine filter type
 * @property {string} operator
 * @property {string} [valueModifier] - contains function name in case operator expects it as right operand
 * @property {boolean} [hasArrayValue] - determine if operator expects array as right operand
 * @property {*} [value] - presents if operation expects specific value in right operand (like `empty` operation)
 */

/**
 * Defines interface and implements base functionality of FilterTranslatorFromExpression
 */
class AbstractFilterTranslatorFromExpression {
    /**
     * The filter type that has to be matched to `filterConfig.type`
     * (has to defined in descendant FilterTranslatorFromExpression)
     * @type {string}
     */
    static TYPE = void 0;

    /**
     * Map object of possible expression's operation to its value in filter
     * (can be defined in descendant FilterTranslatorFromExpression)
     * @type {Object}
     */
    static OPERATOR_MAP = null;

    /**
     * @param {FieldIdTranslatorFromExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @constructor
     * @throws TypeError if some required argument is missing
     */
    constructor(fieldIdTranslator, filterConfigProvider) {
        if (!fieldIdTranslator) {
            throw new TypeError(
                'Instance of `FieldIdTranslatorFromExpression` is required for `FilterTranslatorFromExpression`');
        }
        if (!filterConfigProvider) {
            throw new TypeError( 'Instance of `FilterConfigProvider` is required for `FilterTranslatorFromExpression`');
        }
        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigProvider = filterConfigProvider;
    }

    /**
     * Takes attempt to translate ExpressionLanguage AST node to condition object.
     * If the node is not acceptable - returns null.
     *
     * @param {Node} node ExpressionLanguage AST node
     * @return {Object|null} condition
     */
    tryToTranslate(node) {
        const operatorParams = this.resolveOperatorParams(node);

        if (operatorParams) {
            const filterConfig = this.getFilterConfig(node);

            if (this.checkFilterType(filterConfig) && this.checkOperation(filterConfig, operatorParams)) {
                return this.translate(node, filterConfig, operatorParams);
            }
        }

        return null;
    }

    /**
     * Checks if a node can be used as right operand of `in` operation
     *
     * @param {Node} node
     * @param {function(Node)} [callback] - will be implemented to each array item and has to return boolean
     * @return {boolean}
     */
    checkListOperandAST(node, callback) {
        return node instanceof ArrayNode &&
            isIndexedArrayNode(node) && (
            !callback || node.getKeyValuePairs().every(pair => callback(pair.value))
        );
    }

    /**
     * Defines which node of provided AST represent property path
     *
     * @param {Node} node ExpressionLanguage AST node
     * @return {Node} node
     * @protected
     */
    resolveFieldAST(node) {
        return node.nodes[0];
    }

    /**
     * Finds correspond filter type using operator map
     *
     * @param {Node} node - processed Node
     * @return {OperatorParams|null}
     * @protected
     * @abstract
     */
    resolveOperatorParams(node) {
        throw new Error('Method `resolveOperatorParams` has to defined in descendant FilterTranslatorFromExpression');
    }

    /**
     * Translates the provided node to condition object
     *
     * @param {Node} node ExpressionLanguage AST node
     * @param {Object} filterConfig
     * @param {OperatorParams} operatorParams
     * @returns {Object}
     * @protected
     * @abstract
     */
    translate(node, filterConfig, operatorParams) {
        throw new Error('Method `translate` has to defined in descendant FilterTranslatorFromExpression');
    }

    /**
     * Checks if the provided filter config corresponds to this filter translator
     *
     * @param {Object} filterConfig
     * @returns {boolean}
     * @protected
     */
    checkFilterType(filterConfig) {
        return filterConfig.type === this.constructor.TYPE;
    }

    /**
     * Checks if operation in binary node corresponds to possible choices of filter
     *
     * @param {Object} filterConfig
     * @param {OperatorParams} operatorParams
     * @returns {boolean}
     * @protected
     */
    checkOperation(filterConfig, operatorParams) {
        return filterConfig.choices.some(option => option.value === operatorParams.criterion);
    }

    /**
     * Fetches filter config from ExpressionLanguage AST node
     *
     * @param {Node} node ExpressionLanguage AST node
     * @returns {Object}
     * @protected
     */
    getFilterConfig(node) {
        const fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));
        const fieldSignature = this.fieldIdTranslator.provider.getFieldSignatureSafely(fieldId);
        return this.filterConfigProvider.getApplicableFilterConfig(fieldSignature);
    }
}

export default AbstractFilterTranslatorFromExpression;

