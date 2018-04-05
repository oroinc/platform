define(function(require) {
    'use strict';

    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var tools = ExpressionLanguageLibrary.tools;

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
         * The filter type that has to be matched to `filterConfig.type`
         * (has to defined in descendant FilterTranslatorFromExpression)
         * @type {string}
         */
        filterType: void 0,

        /**
         * Map object of possible expression's operation to its value in filter
         * (can be defined in descendant FilterTranslatorFromExpression)
         * @type {Object}
         */
        operatorMap: null,

        /**
         * Takes attempt to translate ExpressionLanguage AST node to condition object.
         * If the node is not acceptable - returns null.
         *
         * @param {Node} node ExpressionLanguage AST node
         * @return {Object|null} condition
         */
        tryToTranslate: function(node) {
            var operatorParams = this.resolveOperatorParams(node);

            if (operatorParams) {
                var filterConfig = this.getFilterConfig(node);

                if (this.checkFilterType(filterConfig) && this.checkOperation(filterConfig, operatorParams)) {
                    return this.translate(node, filterConfig, operatorParams);
                }
            }

            return null;
        },

        /**
         * Checks if a node can be used as right operand of `in` operation
         *
         * @param {Node} node
         * @param {function(Node)} [callback] - will be implemented to each array item and has to return boolean
         * @return {boolean}
         */
        checkListOperandAST: function(node, callback) {
            return node instanceof ArrayNode &&
                tools.isIndexedArrayNode(node) &&
                (!callback || _.every(node.getKeyValuePairs(), function(pair) {
                    return callback(pair.value);
                }));
        },

        /**
         * Defines which node of provided AST represent property path
         *
         * @param {Node} node ExpressionLanguage AST node
         * @return {Node} node
         * @protected
         */
        resolveFieldAST: function(node) {
            return node.nodes[0];
        },

        /**
         * Finds correspond filter type using operator map
         *
         * @param {Node} node - processed Node
         * @return {OperatorParams|null}
         * @protected
         * @abstract
         */
        resolveOperatorParams: function(node) {
            throw new Error(
                'Method `resolveOperatorParams` has to defined in descendant FilterTranslatorFromExpression');
        },

        /**
         * Translates the provided node to condition object
         *
         * @param {Node} node ExpressionLanguage AST node
         * @param {Object} filterConfig
         * @param {OperatorParams} operatorParams
         * @return {Object}
         * @protected
         * @abstract
         */
        translate: function(node, filterConfig, operatorParams) {
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
         * @param {Object} filterConfig
         * @param {OperatorParams} operatorParams
         * @returns {boolean}
         * @protected
         */
        checkOperation: function(filterConfig, operatorParams) {
            return _.pluck(filterConfig.choices, 'value').indexOf(operatorParams.criterion) !== -1;
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
