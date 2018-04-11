define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'oroquerydesigner/js/query-type-converter/filter-translators-manager'
    ], function(filterTranslatorsManager) {
        var translatorsToExpression = [
            'oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator',
            'oroquerydesigner/js/query-type-converter/to-expression/date-filter-translator',
            'oroquerydesigner/js/query-type-converter/to-expression/datetime-filter-translator',
            'oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator',
            'oroquerydesigner/js/query-type-converter/to-expression/number-filter-translator',
            'oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator'
        ];

        var translatorsFromExpression = [
            'oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator',
            'oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator'
        ];

        filterTranslatorsManager.loadTranslatorsToExpression(translatorsToExpression);
        filterTranslatorsManager.loadTranslatorsFromExpression(translatorsFromExpression);
    });
});
