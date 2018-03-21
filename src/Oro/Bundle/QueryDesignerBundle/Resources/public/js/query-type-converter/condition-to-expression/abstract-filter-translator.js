define(function() {
    'use strict';

    /**
     * Defines interface and implements base functionality of FilterTranslator
     *
     * @param {FieldIdTranslator} fieldIdTranslator
     * @param {Array.<{type: string, name: string}>} filterConfigs
     * @constructor
     * @throws TypeError if instance of FieldIdTranslator is missing
     */
    function AbstractFilterTranslator(fieldIdTranslator, filterConfigs) {
        if (!fieldIdTranslator) {
            throw new TypeError('Instance of `FieldIdTranslator` is required for `FilterTranslator`');
        }
        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigs = filterConfigs;
    }

    Object.assign(AbstractFilterTranslator.prototype, {
        constructor: AbstractFilterTranslator,

        /**
         * Takes condition object and checks if it has valid structure and can be translated to AST
         *
         * @param {Object} condition
         * @return {boolean}
         */
        test: function(condition) {
            throw new Error('Method `test` has to be defined in descendant FilterTranslator');
        },

        /**
         * Takes condition object and translates it to ExpressionLanguage AST, if the condition passes the test.
         * Otherwise returns null
         *
         * @param {Object} condition
         * @return {Node|null} ExpressionLanguage AST node
         */
        translate: function(condition) {
            var result = null;
            if (this.test(condition)) {
                result = this._translate(condition);
            }
            return result;
        },

        /**
         * Takes condition object and translates it to ExpressionLanguage AST
         *
         * @param {Object} condition
         * @return {Node} ExpressionLanguage AST node
         * @protected
         */
        _translate: function(condition) {
            throw new Error('Method `_translate` has to be defined in descendant FilterTranslator');
        }
    });

    return AbstractFilterTranslator;
});
