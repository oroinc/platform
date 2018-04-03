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
        operatorMap: StringFilterTranslatorToExpression.prototype.operatorMap,

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
            var map = _.mapObject(this.operatorMap, function(val, key) {
                return _.extend({
                    type: key,
                    hasArrayValue: false,
                    valueModifier: void 0
                }, val);
            });

            map = _.where(map, {operator: node.attrs.operator});

            if (map.length === 0) {
                return null;
            }

            if (this.checkValueNode(valueNode)) {
                map =_.where(map, {hasArrayValue: false, valueModifier: void 0});
                if (map.length > 0) {
                    params = _.findWhere(map, {value: valueNode.attrs.value}) || _.first(map);
                }
            } else if (this.checkListOperandAST(valueNode, this.checkValueNode)) {
                params = _.findWhere(map, {hasArrayValue: true});
            } else if (
                valueNode instanceof FunctionNode &&
                valueNode.nodes[0].nodes.length === 1 &&
                this.checkValueNode(valueNode.nodes[0].nodes[0])
            ) {
                params = _.findWhere(map, {valueModifier: valueNode.attrs.name});
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
                        type: operatorParams.type,
                        value: value
                    }
                }
            };

            return condition;
        }
    });

    return StringFilterTranslator;
});
