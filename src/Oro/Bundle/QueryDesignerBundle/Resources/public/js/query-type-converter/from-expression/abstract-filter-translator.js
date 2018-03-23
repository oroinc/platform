define(function() {
    'use strict';

    /**
     * Defines interface and implements base functionality of FilterTranslatorFromExpression
     *
     * @param {FieldIdTranslatorFromExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @constructor
     * @throws TypeError if instance of FieldIdTranslatorFromExpression is missing
     */
    var AbstractFilterTranslator = function AbstractFilterTranslatorFromExpression(
        fieldIdTranslator,
        filterConfigProvider
    ) {
        if (!fieldIdTranslator) {
            throw new TypeError(
                'Instance of `FieldIdTranslatorFromExpression` is required for `FilterTranslatorFromExpression`');
        }
        if (!filterConfigProvider) {
            throw new TypeError( 'Instance of `FilterConfigProvider` is required for `FilterTranslatorFromExpression`');
        }
        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigProvider = filterConfigProvider;
    };

    Object.assign(AbstractFilterTranslator.prototype, {
        constructor: AbstractFilterTranslator,

        /**
         * Map object of possible expression's operation to its value in filter
         * (has to defined in descendant FilterTranslatorFromExpression)
         * @type {Object}
         */
        operatorMap: null,

        /**
         * The filter type that has to be matched to `filterConfig.type`
         * (has to defined in descendant FilterTranslatorFromExpression)
         * @type {string}
         */
        filterType: void 0,

        /**
         * Takes attempt to translate ExpressionLanguage AST node to condition object.
         * If the node is not acceptable - returns null.
         *
         * @param {Node} node ExpressionLanguage AST node
         * @return {Object|null} condition
         */
        tryToTranslate: function(node) {
            if (this.checkAST(node)) {
                var filterConfig = this.getFilterConfig(node);

                if (this.checkFilterType(filterConfig) && this.checkOperation(node, filterConfig)) {
                    return this.translate(node, filterConfig);
                }
            }

            return null;
        },

        /**
         * Check if structure of the node corresponds to expected node for translation
         *
         * @param {Node} node ExpressionLanguage AST node
         * @return {boolean}
         * @protected
         * @abstract
         */
        checkAST: function(node) {
            throw new Error('Method `checkAST` has to defined in descendant FilterTranslatorFromExpression');
        },

        /**
         * Defines which node of provided AST represent property path
         *
         * @param {Node} node ExpressionLanguage AST node
         * @return {Node} node
         * @protected
         * @abstract
         */
        resolveFieldAST: function(node) {
            throw new Error('Method `resolveFieldAST` has to defined in descendant FilterTranslatorFromExpression');
        },

        /**
         * Translates the provided node to condition object
         *
         * @param {Node} node ExpressionLanguage AST node
         * @param {Object} filterConfig
         * @return {Object}
         * @protected
         * @abstract
         */
        translate: function(node, filterConfig) {
            throw new Error('Method `translate` has to defined in descendant FilterTranslatorFromExpression');
        },

        /**
         * Checks if the provided filter config corresponds to this filter translator
         *
         * @param {Object} filterConfig
         * @returns {boolean}
         * @protected
         */
        checkFilterType: function(filterConfig) {
            return filterConfig.type === this.filterType;
        },

        /**
         * Checks if operation in binary node corresponds to possible choices of filter
         *
         * @param {Node} node ExpressionLanguage AST node
         * @param {Object} filterConfig
         * @returns {boolean}
         * @protected
         */
        checkOperation: function(node, filterConfig) {
            return _.pluck(filterConfig.choices, 'value').indexOf(this.operatorMap[node.attrs.operator]) !== -1;
        },

        /**
         * Fetches filter config from ExpressionLanguage AST node
         *
         * @param {Node} node ExpressionLanguage AST node
         * @returns {Object}
         * @protected
         */
        getFilterConfig: function(node) {
            var fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));
            var fieldSignature = this.fieldIdTranslator.provider.getFieldSignatureSafely(fieldId);
            return this.filterConfigProvider.getApplicableFilterConfig(fieldSignature);
        }
    });

    return AbstractFilterTranslator;
});
