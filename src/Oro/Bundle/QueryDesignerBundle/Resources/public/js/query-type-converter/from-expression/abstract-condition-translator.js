class AbstractConditionTranslatorFromExpression {
    /**
     * @param {FieldIdTranslatorFromExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @param {TranslatorProvider} filterTranslatorProvider
     * @constructor
     * @throws TypeError if some required argument is missing
     */
    constructor(fieldIdTranslator, filterConfigProvider, filterTranslatorProvider) {
        if (!fieldIdTranslator) {
            throw new TypeError(
                'Instance of `fieldIdTranslator` is required for `ConditionTranslatorFromExpression`');
        }
        if (!filterConfigProvider) {
            throw new TypeError(
                'Instance of `filterConfigProvider` is required for `ConditionTranslatorFromExpression`');
        }
        if (!filterTranslatorProvider) {
            throw new TypeError(
                'Instance of `TranslatorProvider` is required for `ConditionTranslatorFromExpression`');
        }

        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigProvider = filterConfigProvider;
        this.filterTranslatorProvider = filterTranslatorProvider;
    }

    /**
     * Takes attempt to translate ExpressionLanguage AST to condition object.
     * If AST is not supported - returns null.
     *
     * @param {Node} node ExpressionLanguage AST node
     * @return {Object|null} condition
     */
    tryToTranslate(node) {
        return this.translate(node);
    }

    /**
     * Translates provided AST to condition object
     *
     * @param {Node} node ExpressionLanguage AST node
     * @returns {Object|null}
     * @protected
     * @abstract
     */
    translate(node) {
        throw new Error('Method `translate` has to defined in descendant ConditionTranslatorFromExpression');
    }

    /**
     * Finds filter translator by its configuration
     *
     * @param {Object} filterConfig
     * @return {AbstractFilterTranslatorFromExpression|null}
     * @protected
     */
    resolveFilterTranslator(filterConfig) {
        let FilterTranslator;
        let filterTranslator;
        if (
            filterConfig &&
            (FilterTranslator = this.filterTranslatorProvider.getTranslatorConstructor(filterConfig.type))
        ) {
            filterTranslator = new FilterTranslator(this.fieldIdTranslator, this.filterConfigProvider);
        }
        return filterTranslator || null;
    }
}

export default AbstractConditionTranslatorFromExpression;
