define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator');
    var DictionaryFilterTranslatorToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;

    /**
     * @inheritDoc
     */
    var DictionaryFilterTranslator = function DictionaryFilterTranslatorFromExpression() {
        DictionaryFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    DictionaryFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    DictionaryFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(DictionaryFilterTranslator.prototype, {
        constructor: DictionaryFilterTranslator,

        /**
         * @inheritDoc
         */
        operatorMap: _.invert(DictionaryFilterTranslatorToExpression.prototype.operatorMap),

        /**
         * @inheritDoc
         */
        filterType: 'dictionary',

        /**
         * @inheritDoc
         */
        checkAST: function(node) {
            return node instanceof BinaryNode &&
                node.attrs.operator in this.operatorMap &&
                node.nodes[0] instanceof GetAttrNode &&
                node.nodes[1] instanceof ArrayNode &&
                _.every(node.nodes[1].nodes, function(node) {
                    return node instanceof ConstantNode;
                });
        },

        /**
         * @inheritDoc
         */
        resolveFieldAST: function(node) {
            return node.nodes[0];
        },

        /**
         * @inheritDoc
         */
        translate: function(node, filterConfig) {
            var fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

            var condition = {
                columnName: fieldId,
                criterion: {
                    filter: filterConfig.name,
                    data: {
                        type: this.operatorMap[node.attrs.operator],
                        value: _.map(node.nodes[1].getKeyValuePairs(), function(pair) {
                            return String(pair.value.attrs.value);
                        })
                    }
                }
            };

            if (filterConfig.filterParams) {
                condition.criterion.data.params = filterConfig.filterParams;
            }

            return condition;
        }
    });

    return DictionaryFilterTranslator;
});
