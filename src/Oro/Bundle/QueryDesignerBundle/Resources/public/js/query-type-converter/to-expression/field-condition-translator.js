define(function(require) {
    'use strict';

    var AbstractConditionTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-condition-translator');

    /**
     * @inheritDoc
     */
    var FieldConditionTranslator = function FieldConditionTranslatorToExpression() {
        FieldConditionTranslator.__super__.constructor.apply(this, arguments);
    };

    FieldConditionTranslator.prototype = Object.create(AbstractConditionTranslator.prototype);
    FieldConditionTranslator.__super__ = AbstractConditionTranslator.prototype;

    Object.assign(FieldConditionTranslator.prototype, {
        constructor: FieldConditionTranslator,

        /**
         * @inheritDoc
         */
        getConditionSchema: function() {
            return {
                type: 'object',
                required: ['columnName', 'criterion'],
                properties: {
                    columnName: {type: 'string'},
                    criterion: {
                        type: 'object',
                        required: ['data', 'filter'],
                        properties: {
                            filter: {type: 'string'},
                            data: {type: 'object'}
                        }
                    }
                }
            };
        },

        /**
         * @inheritDoc
         */
        test: function(condition) {
            var filterTranslator;
            var result = FieldConditionTranslator.__super__.test.call(this, condition);

            if (result) {
                filterTranslator = this.resolveFilterTranslator(condition.criterion.filter) || false;
                result = filterTranslator && filterTranslator.test(condition.criterion.data);
            }

            return result;
        },

        /**
         * @inheritDoc
         */
        translate: function(condition) {
            var filterTranslator = this.resolveFilterTranslator(condition.criterion.filter);
            var leftOperand = this.fieldIdTranslator.translate(condition.columnName);

            return filterTranslator.translate(leftOperand, condition.criterion.data);
        }
    });

    return FieldConditionTranslator;
});
