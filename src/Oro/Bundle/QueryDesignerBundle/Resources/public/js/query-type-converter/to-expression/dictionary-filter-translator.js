define(function(require) {
    'use strict';

    var jsonSchemaValidator = require('oroui/js/tools/json-schema-validator');
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

        operatorMap: {
            1: 'in', // TYPE_IN (is any of)
            2: 'not in', // TYPE_NOT_IN (is not any of)
            3: '=', // EQUAL
            4: '!=' // NOT_EQUAL
        },

        /**
         * @inheritDoc
         */
        test: function(condition) {
            var result = false;
            var filterConfigs = this.filterConfigProvider.getFilterConfigsByType('dictionary');
            var schema = {
                type: 'object',
                required: ['columnName', 'criterion'],
                properties: {
                    columnName: {type: 'string'},
                    criterion: {
                        type: 'object',
                        required: ['data', 'filter'],
                        properties: {
                            filter: {
                                'type': 'string',
                                'enum': _.pluck(filterConfigs, 'name')
                            },
                            data: {
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
                            }
                        }
                    }
                }
            };

            if (jsonSchemaValidator.validate(schema, condition)) {
                var config = _.findWhere(filterConfigs, {name: condition.criterion.filter});
                result = _.pluck(config.choices, 'value').indexOf(condition.criterion.data.type) !== -1;
            }

            return result;
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
