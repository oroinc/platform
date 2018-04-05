define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator');
    var StringFilterTranslatorToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var FunctionNode = ExpressionLanguageLibrary.FunctionNode;

    var operatorMap = _.mapObject(StringFilterTranslatorToExpression.prototype.operatorMap, function(val, key) {
        return _.extend({
            criterion: key,
            hasArrayValue: false,
            valueModifier: void 0
        }, val);
    });

    /**
     * @inheritDoc
     */
    var StringFilterTranslator = function StringFilterTranslatorFromExpression() {
        StringFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    StringFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    StringFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(StringFilterTranslator.prototype, {
        constructor: StringFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'string',

        /**
         * @inheritDoc
         */
        operatorMap: operatorMap,

        /**
         * Checks if node has correct type and value
         *
         * @param {Node} node
         * @return {boolean}
         */
        checkValueNode: function(node) {
            return node instanceof ConstantNode && _.isString(node.attrs.value);
        },

        /**
         * @inheritDoc
         */
        resolveOperatorParams: function(node) {
            if (!(node instanceof BinaryNode && this.resolveFieldAST(node) instanceof GetAttrNode)) {
                return null;
            }

            var params;
            var valueNode = node.nodes[1];
            var matchedParams = _.where(this.operatorMap, {operator: node.attrs.operator});

            if (matchedParams.length === 0) {
                return null;
            }

            if (this.checkValueNode(valueNode)) {
                matchedParams =_.where(matchedParams, {hasArrayValue: false, valueModifier: void 0});
                if (matchedParams.length > 0) {
                    params = _.findWhere(matchedParams, {value: valueNode.attrs.value}) || _.first(matchedParams);
                }
            } else if (this.checkListOperandAST(valueNode, this.checkValueNode)) {
                params = _.findWhere(matchedParams, {hasArrayValue: true});
            } else if (
                valueNode instanceof FunctionNode &&
                valueNode.nodes[0].nodes.length === 1 &&
                this.checkValueNode(valueNode.nodes[0].nodes[0])
            ) {
                params = _.findWhere(matchedParams, {valueModifier: valueNode.attrs.name});
            }

            return params || null;
        },

        /**
         * @inheritDoc
         */
        translate: function(node, filterConfig, operatorParams) {
            var value;
            var fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));
            var valueNode = node.nodes[1];

            if (operatorParams.valueModifier) {
                value = valueNode.nodes[0].nodes[0].attrs.value;
            } else if (operatorParams.hasArrayValue) {
                value = _.map(node.nodes[1].getKeyValuePairs(), function(pair) {
                    return String(pair.value.attrs.value);
                }).join(', ');
            } else {
                value = valueNode.attrs.value;
            }

            var condition = {
                columnName: fieldId,
                criterion: {
                    filter: filterConfig.name,
                    data: {
                        type: operatorParams.criterion,
                        value: value
                    }
                }
            };

            return condition;
        }
    });

    return StringFilterTranslator;
});
