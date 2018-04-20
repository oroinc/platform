define(function(require) {
    'use strict';

    var AbstractConditionTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-condition-translator');

    /**
     * Defines interface and implements base functionality of ConditionTranslatorToExpression
     *
     * @param {FieldIdTranslatorToExpression} fieldIdTranslator
     * @param {FilterConfigProvider} filterConfigProvider
     * @param {TranslatorProvider} filterTranslatorProvider
     * @constructor
     * @throws TypeError if some required argument is missing
     */
    var FieldConditionTranslator = function FieldConditionTranslatorToExpression(
        fieldIdTranslator,
        filterConfigProvider,
        filterTranslatorProvider
    ) {
        if (!fieldIdTranslator) {
            throw new TypeError(
                'Instance of `FieldIdTranslatorToExpression` is required for `FieldConditionTranslatorToExpression`');
        }
        if (!filterConfigProvider) {
            throw new TypeError(
                'Instance of `FilterConfigProvider` is required for `FieldConditionTranslatorToExpression`');
        }
        if (!filterTranslatorProvider) {
            throw new TypeError(
                'Instance of `TranslatorProvider` is required for `FieldConditionTranslatorToExpression`');
        }

        this.fieldIdTranslator = fieldIdTranslator;
        this.filterConfigProvider = filterConfigProvider;
        this.filterTranslatorProvider = filterTranslatorProvider;

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
        },

        /**
         * Finds filter translate by its name
         *
         * @param {string} filterName
         * @return {AbstractFilterTranslatorToExpression|null}
         * @protected
         */
        resolveFilterTranslator: function(filterName) {
            var FilterTranslator;
            var filterTranslator;
            var filterConfig = this.filterConfigProvider.getFilterConfigByName(filterName);
            if (
                filterConfig &&
                (FilterTranslator = this.filterTranslatorProvider.getTranslator(filterConfig.type))
            ) {
                filterTranslator = new FilterTranslator(filterConfig);
            }
            return filterTranslator || null;
        }
    });

    return FieldConditionTranslator;
});
