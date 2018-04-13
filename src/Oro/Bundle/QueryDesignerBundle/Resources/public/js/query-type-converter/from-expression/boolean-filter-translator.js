define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator');
    var BooleanFilterTranslatorToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var UnaryNode = ExpressionLanguageLibrary.UnaryNode;

    /**
     * @inheritDoc
     */
    var BooleanFilterTranslator = function BooleanFilterTranslatorFromExpression() {
        BooleanFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    BooleanFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    BooleanFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(BooleanFilterTranslator.prototype, {
        constructor: BooleanFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'boolean',

        /**
         * Resolved binary operations
         * @type {Array}
         */
        binaryOperators: ['=', '=='],

        /**
         * Resolved unary operations
         * @type {Array}
         */
        unaryOperators: ['!', 'not'],

        /**
         * @inheritDoc
         */
        valueMap: BooleanFilterTranslatorToExpression.prototype.valueMap,

        /**
         * @inheritDoc
         */
        resolveFieldAST: function(node) {
            if (node instanceof GetAttrNode) {
                return node;
            } else {
                return node.nodes[0];
            }
        },

        /**
         * @inheritDoc
         */
        checkOperation: function(filterConfig, operatorParams) {
            return _.pluck(filterConfig.choices, 'value').indexOf(operatorParams.value) !== -1;
        },

        /**
         * @inheritDoc
         */
        resolveOperatorParams: function(node) {
            var params = null;
            if (node instanceof BinaryNode &&
                this.binaryOperators.indexOf(node.attrs.operator) !== -1 &&
                node.nodes[0] instanceof GetAttrNode &&
                node.nodes[1] instanceof ConstantNode &&
                _.isBoolean(node.nodes[1].attrs.value)
            ) {
                params = {
                    value: node.nodes[1].attrs.value
                };
            } else if (node instanceof UnaryNode &&
                this.unaryOperators.indexOf(node.attrs.operator) !== -1
            ) {
                params = {
                    value: false
                };
            } else if (node instanceof GetAttrNode) {
                params = {
                    value: true
                };
            }

            if (params) {
                params.value = this.valueMap[String(params.value)];
            }

            return params;
        },

        /**
         * @inheritDoc
         */
        translate: function(node, filterConfig, operatorParams) {
            var fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

            return {
                columnName: fieldId,
                criterion: {
                    filter: filterConfig.name,
                    data: {
                        value: operatorParams.value
                    }
                }
            };
        }
    });

    return BooleanFilterTranslator;
});
