define(function(require) {
    'use strict';

    var BaseController = require('oroui/js/app/controllers/base/controller');
    var TranslatorProvider = require('oroquerydesigner/js/query-type-converter/translator-provider');

    // setup provider for FILTER FROM EXPRESSION translator
    BaseController.loadBeforeAction([
        'oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator',
        'oroquerydesigner/js/query-type-converter/from-expression/boolean-filter-translator',
        'oroquerydesigner/js/query-type-converter/from-expression/date-filter-translator',
        'oroquerydesigner/js/query-type-converter/from-expression/datetime-filter-translator',
        'oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator',
        // 'oroquerydesigner/js/query-type-converter/from-expression/number-filter-translator',
        'oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator'
    ], function(AbstractFilterTranslatorFromExpression) {
        var provider = TranslatorProvider
            .createProvider('filterFromExpression', AbstractFilterTranslatorFromExpression);
        Array.prototype.slice.call(arguments, 1).forEach(function(Translator) {
            provider.registerTranslator(Translator.prototype.filterType, Translator);
        });
    });

    // setup provider for FILTER TO EXPRESSION translator
    BaseController.loadBeforeAction([
        'oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/date-filter-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/datetime-filter-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator'
    ], function(AbstractFilterTranslatorToExpression) {
        var provider = TranslatorProvider
            .createProvider('filterToExpression', AbstractFilterTranslatorToExpression);
        Array.prototype.slice.call(arguments, 1).forEach(function(Translator) {
            provider.registerTranslator(Translator.prototype.filterType, Translator);
        });
    });

    // setup provider for CONDITION TO EXPRESSION translator
    BaseController.loadBeforeAction([
        'oroquerydesigner/js/query-type-converter/to-expression/abstract-condition-translator',
        'oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator'
    ], function(AbstractConditionTranslatorToExpression) {
        var provider = TranslatorProvider
            .createProvider('conditionToExpression', AbstractConditionTranslatorToExpression);
        Array.prototype.slice.call(arguments, 1).forEach(function(Translator) {
            provider.registerTranslator(Translator.name, Translator);
        });
    });
});
