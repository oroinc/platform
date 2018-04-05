define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var Node = ExpressionLanguageLibrary.Node;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var FunctionNode = ExpressionLanguageLibrary.FunctionNode;
    var tools = ExpressionLanguageLibrary.tools;

    /**
     * @inheritDoc
     */
    var StringFilterTranslator = function StringFilterTranslatorToExpression() {
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
        operatorMap: {
            1: { // contains
                operator: 'matches',
                valueModifier: 'containsRegExp'
            },
            2: { // does not contain
                operator: 'not matches',
                valueModifier: 'containsRegExp'
            },
            3: { // is equal to
                operator: '='
            },
            4: { // starts with
                operator: 'matches',
                valueModifier: 'startWithRegExp'
            },
            5: { // ends with
                operator: 'matches',
                valueModifier: 'endWithRegExp'
            },
            6: { // is any of
                operator: 'in',
                hasArrayValue: true
            },
            7: { // is not any of
                operator: 'not in',
                hasArrayValue: true
            },
            filter_empty_option: { // is empty
                operator: '=',
                value: ''
            },
            filter_not_empty_option: { // is not empty
                operator: '!=',
                value: ''
            }
        },

        /**
         * @inheritDoc
         */
        getFilterValueSchema: function() {
            return {
                type: 'object',
                required: ['type', 'value'],
                properties: {
                    type: {type: 'string'},
                    value: {type: 'string'}
                }
            };
        },

        /**
         * @inheritDoc
         */
        translate: function(leftOperand, filterValue) {
            var rightOperand;
            var value = filterValue.value;
            var params = this.operatorMap[filterValue.type];

            if (params.hasArrayValue) {
                rightOperand = tools.createArrayNode(this.splitValues(value));
            } else if (params.valueModifier) {
                rightOperand = new FunctionNode(
                    params.valueModifier,
                    new Node([new ConstantNode(value)])
                );
            } else if ('value' in params) {
                rightOperand = new ConstantNode(params.value);
            } else {
                rightOperand = new ConstantNode(filterValue.value);
            }

            return new BinaryNode(params.operator, leftOperand, rightOperand);
        }
    });

    return StringFilterTranslator;
});
