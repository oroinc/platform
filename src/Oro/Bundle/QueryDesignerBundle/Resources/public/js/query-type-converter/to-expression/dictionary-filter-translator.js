define(function(require) {
    'use strict';

    var AbstractFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;

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
        translate: function(condition) {
            var values = new ArrayNode();
            condition.criterion.data.value.forEach(function(val) {
                values.addElement(new ConstantNode(val));
            });

            return new BinaryNode(
                this.operatorMap[condition.criterion.data.type],
                this.fieldIdTranslator.translate(condition.columnName),
                values
            );
        }
    });

    return DictionaryFilterTranslator;
});
