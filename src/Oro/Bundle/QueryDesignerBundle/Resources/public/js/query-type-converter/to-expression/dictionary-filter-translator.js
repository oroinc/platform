define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;

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

        operatorMap: {
            1: 'in', // TYPE_IN (is any of)
            2: 'not in', // TYPE_NOT_IN (is not any of)
            3: '=', // EQUAL
            4: '!=' // NOT_EQUAL
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
            return new BinaryNode(
                this.operatorMap[filterValue.type],
                leftOperand,
                ExpressionLanguageLibrary.tools.createArrayNode(filterValue.value)
            );
        }
    });

    return DictionaryFilterTranslator;
});
