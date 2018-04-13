define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var tools = ExpressionLanguageLibrary.tools;

    /**
     * @inheritDoc
     */
    var DictionaryFilterTranslator = function DictionaryFilterTranslatorToExpression() {
        DictionaryFilterTranslator.__super__.constructor.apply(this, arguments);
    };

    DictionaryFilterTranslator.prototype = Object.create(AbstractFilterTranslator.prototype);
    DictionaryFilterTranslator.__super__ = AbstractFilterTranslator.prototype;

    Object.assign(DictionaryFilterTranslator.prototype, {
        constructor: DictionaryFilterTranslator,

        /**
         * @inheritDoc
         */
        filterType: 'dictionary',

        /**
         * @inheritDoc
         */
        operatorMap: {
            1: { // TYPE_IN (is any of)
                operator: 'in'
            },
            2: { // TYPE_NOT_IN (is not any of)
                operator: 'not in'
            },
            3: { // EQUAL
                operator: '='
            },
            4: { // NOT_EQUAL
                operator: '!='
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
                    value: {
                        type: 'array',
                        items: {type: 'string'}
                    },
                    params: {type: 'object'}
                }
            };
        },

        /**
         * @inheritDoc
         */
        translate: function(leftOperand, filterValue) {
            var operatorParams = this.operatorMap[filterValue.type];
            var rightOperand = tools.createArrayNode(filterValue.value);

            return new BinaryNode(operatorParams.operator, leftOperand, rightOperand);
        }
    });

    return DictionaryFilterTranslator;
});
