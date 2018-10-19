define(function(require) {
    'use strict';

    var TranslatorProvider = require('oroquerydesigner/js/query-type-converter/translator-provider');

    // setup provider for FILTER FROM EXPRESSION translator
    var filterFromExpression = [
        require('oroquerydesigner/js/query-type-converter/from-expression/abstract-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/from-expression/boolean-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/from-expression/date-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/from-expression/datetime-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator'),
        // require('oroquerydesigner/js/query-type-converter/from-expression/number-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator')
    ];

    var filterFromExpressionProvider = TranslatorProvider
        .createProvider('filterFromExpression', filterFromExpression.shift());

    filterFromExpression.forEach(function(Translator) {
        filterFromExpressionProvider.registerTranslator(Translator.prototype.filterType, Translator);
    });

    // setup provider for FILTER TO EXPRESSION translator
    var filterToExpression = [
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/date-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/datetime-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator')
    ];

    var filterToExpressionProvider = TranslatorProvider
        .createProvider('filterToExpression', filterToExpression.shift());

    filterToExpression.forEach(function(Translator) {
        filterToExpressionProvider.registerTranslator(Translator.prototype.filterType, Translator);
    });

    // setup provider for CONDITION TO EXPRESSION translator
    var conditionToExpression = [
        require('oroquerydesigner/js/query-type-converter/to-expression/abstract-condition-translator'),
        require('oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator')
    ];

    var conditionToExpressionProvider = TranslatorProvider
        .createProvider('conditionToExpression', conditionToExpression.shift());

    conditionToExpression.forEach(function(Translator) {
        conditionToExpressionProvider.registerTranslator(Translator.name, Translator);
    });
});
