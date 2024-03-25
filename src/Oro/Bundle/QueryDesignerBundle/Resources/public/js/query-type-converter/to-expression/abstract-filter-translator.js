import jsonSchemaValidator from 'oroui/js/tools/json-schema-validator';

/**
 * Defines interface and implements base functionality of FilterTranslatorToExpression
 */
class AbstractFilterTranslatorToExpression {
    /**
     * The filter type that has to be matched to `filterConfig.type`
     * (has to be defined in descendant FilterTranslatorToExpression)
     * @type {string}
     */
    static TYPE = void 0;

    /**
     * Map object of possible filter criteria value to expression's operation
     * (can be defined in descendant FilterTranslatorToExpression)
     * @type {Object}
     */
    static OPERATOR_MAP = null;

    /**
     * Character that is used in filter value to input plural values in text field
     * @type {string}
     */
    static VALUES_SEPARATOR = ',';

    /**
     * @param filterConfig
     * @constructor
     * @throws TypeError if filterConfig is missing
     */
    constructor(filterConfig) {
        if (!filterConfig) {
            throw new TypeError('`filterConfig` is required for `FilterTranslatorToExpression`');
        }
        this.filterConfig = filterConfig;
    }

    /**
     * Builds filter value's part of JSON validation schema
     *
     * @return {Object}
     * @protected
     * @abstract
     */
    getFilterValueSchema() {
        throw new Error('Method `getFilterValueSchema` has to be defined in descendant FilterTranslatorToExpression');
    }

    /**
     * Takes filterValue object and checks if it has valid structure and can be translated to AST
     *
     * @param {Object} filterValue
     * @return {boolean}
     * @protected
     */
    test(filterValue) {
        const schema = this.getFilterValueSchema();

        return jsonSchemaValidator.validate(schema, filterValue) &&
            this.testToOperatorMap(filterValue) &&
            this.testToConfig(filterValue);
    }

    /**
     * Check if type of filter can be mapped to operator config
     *
     * @param {Object} filterValue
     * @return {boolean}
     */
    testToOperatorMap(filterValue) {
        return filterValue.type in this.constructor.OPERATOR_MAP;
    }

    /**
     * Check if the filter type complies to the filter config
     *
     * @param {Object} filterValue
     * @return {boolean}
     */
    testToConfig(filterValue) {
        return this.filterConfig.choices.some(option => option.value === String(filterValue.type));
    }

    /**
     * Takes filterValue and translates it to ExpressionLanguage AST with provided leftOperand
     *
     * @param {Node} leftOperand
     * @param {Object} filterValue
     * @return {Node|null} ExpressionLanguage AST node
     * @protected
     * @abstract
     */
    translate(leftOperand, filterValue) {
        throw new Error('Method `translate` has to be defined in descendant FilterTranslatorToExpression');
    }

    /**
     * Splits value to array of string values when filter supports plural values
     *
     * @param {string} value
     * @return {Array.<string>}
     * @protected
     */
    splitValues(value) {
        return value.split(this.constructor.VALUES_SEPARATOR).map(item => item.trim());
    }
}

export default AbstractFilterTranslatorToExpression;
