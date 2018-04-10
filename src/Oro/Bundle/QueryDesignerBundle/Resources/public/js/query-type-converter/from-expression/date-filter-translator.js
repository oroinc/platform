define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator');
    var DateFilterTranslatorToExpression =
        require('oroquerydesigner/js/query-type-converter/to-expression/date-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var FunctionNode = ExpressionLanguageLibrary.FunctionNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var compareAST = ExpressionLanguageLibrary.tools.compareAST;

    var operatorMap = _.mapObject(DateFilterTranslatorToExpression.prototype.operatorMap, function(val, key) {
        return _.extend({criterion: key}, val);
    });
    var partMap = _.mapObject(DateFilterTranslatorToExpression.prototype.partMap, function(val, key) {
        return _.extend({
            part: key
        }, val);
    });

    /**
     * @inheritDoc
     */
    var DateFilterTranslator = function DateFilterTranslatorFromExpression() {
        DateFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    DateFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    DateFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(DateFilterTranslator.prototype, {
        constructor: DateFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'date',

        /**
         * @inheritDoc
         */
        operatorMap: operatorMap,

        /**
         * Map of value part to its params
         * @type {Object}
         */
        partMap: partMap,

        /**
         * @inheritDoc
         */
        resolveOperatorParams: function(node) {
            if (!(node instanceof BinaryNode)) {
                return null;
            }

            var operatorParams;
            var matchedParams = _.where(this.operatorMap, {operator: node.attrs.operator});

            if (matchedParams.length === 0) {
                return null;
            }

            // clone nested params objects to preserve originals untouched
            matchedParams = _.map(matchedParams, function(params) {
                return _.mapObject(params, _.clone);
            });

            operatorParams = _.find(matchedParams, function(params) {
                var leftNode;
                var rightNode;
                var partParams;
                var valueParams = {};

                if (
                    params.left && params.right &&
                    (leftNode = node.nodes[0]) instanceof BinaryNode &&
                    (rightNode = node.nodes[1]) instanceof BinaryNode &&
                    params.left.operator === leftNode.attrs.operator &&
                    params.right.operator === rightNode.attrs.operator &&
                    // if left operands are identical
                    compareAST(leftNode.nodes[0], rightNode.nodes[0]) &&
                    (partParams = this.resolvePartParams(leftNode.nodes[0])) !== void 0 &&
                    (valueParams.left = this.resolveValueParams(leftNode.nodes[1], partParams)) !== void 0 &&
                    (valueParams.right = this.resolveValueParams(rightNode.nodes[1], partParams)) !== void 0
                ) {
                    _.extend(params, _.pick(partParams, 'part'));
                    _.extend(params.left, valueParams.left);
                    _.extend(params.right, valueParams.right);
                } else if (
                    (partParams = this.resolvePartParams(node.nodes[0])) !== void 0 &&
                    (valueParams = this.resolveValueParams(node.nodes[1], partParams)) !== void 0
                ) {
                    _.extend(params, _.pick(partParams, 'part'), valueParams);
                } else {
                    return false;
                }

                return true;
            }, this);

            return operatorParams || null;
        },

        /**
         * Defines date part params on base of AST node
         *
         * @return {Object|undefined}
         * @protected
         */
        resolvePartParams: function(node) {
            var partParams;
            if (node instanceof FunctionNode) {
                partParams = _.findWhere(this.partMap, {propModifier: node.attrs.name});
            } else if (node instanceof GetAttrNode) {
                partParams = this.partMap.value;
            }
            return partParams;
        },

        /**
         * Defines date value params on base of AST node
         *
         * @return {{value: string}|{variable: string}|undefined}
         * @protected
         */
        resolveValueParams: function(node, partParams) {
            var variable;
            var valueParams;

            if ( // if value a constant code
                node instanceof ConstantNode &&
                _.isString(node.attrs.value) &&
                (!partParams.valuePattern || partParams.valuePattern.test(node.attrs.value))
            ) {
                valueParams = {value: node.attrs.value};
            } else if (// if value a variable
                node instanceof FunctionNode &&
                (variable = _.findKey(partParams.variables, function(value) {
                    return value === node.attrs.name;
                })) !== void 0
            ) {
                valueParams = {variable: variable};
            }

            return valueParams;
        },

        /**
         * @inheritDoc
         */
        resolveFieldAST: function(node) {
            var leftNode = node.nodes[0] instanceof BinaryNode ? node.nodes[0].nodes[0] : node.nodes[0];
            return leftNode instanceof FunctionNode ? leftNode.nodes[0].nodes[0] : leftNode;
        },

        /**
         * @inheritDoc
         */
        translate: function(node, filterConfig, operatorParams) {
            var value = {start: '', end: ''};
            var fieldId = this.fieldIdTranslator.translate(this.resolveFieldAST(node));

            var assignSingleValue = function(params) {
                value[params.valueProp] = params.variable ? '{{' + params.variable + '}}' : params.value;
            };

            if (operatorParams.left && operatorParams.right) {
                assignSingleValue(operatorParams.left);
                assignSingleValue(operatorParams.right);
            } else {
                assignSingleValue(operatorParams);
            }

            return {
                columnName: fieldId,
                criterion: {
                    filter: filterConfig.name,
                    data: {
                        type: operatorParams.criterion,
                        part: operatorParams.part,
                        value: value
                    }
                }
            };
        },

        /**
         * @inheritDoc
         */
        checkOperation: function(filterConfig, operatorParams) {
            var variables = _.compact(_.unique(_.pluck([
                operatorParams,
                operatorParams.left,
                operatorParams.right
            ], 'variable')));

            return DateFilterTranslator.__super__.checkOperation.call(this, filterConfig, operatorParams) &&
                _.keys(filterConfig.dateParts).indexOf(operatorParams.part) !== -1 &&
                variables.length === 0 ||
                filterConfig.externalWidgetOptions &&
                filterConfig.externalWidgetOptions.dateVars &&
                filterConfig.externalWidgetOptions.dateVars[operatorParams.part] &&
                _.all(variables, function(variable) {
                    return !_.isEmpty(filterConfig.externalWidgetOptions.dateVars[operatorParams.part][variable]);
                });
        }
    });

    return DateFilterTranslator;
});
