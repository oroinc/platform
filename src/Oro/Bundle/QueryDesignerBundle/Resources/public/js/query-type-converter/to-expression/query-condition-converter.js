define(function(require) {
    'use strict';

    var QueryConditionConverter = function QueryConditionConverterToExpression(conditionTranslators) {
        if (!conditionTranslators) {
            throw new TypeError(
                '`conditionTranslators` are required for `QueryConditionConverterToExpression`');
        }
        this.translators = conditionTranslators;
    };

    Object.assign(QueryConditionConverter.prototype, {
        constructor: QueryConditionConverter,

        convert: function(condition) {
            // console.warn(condition);
        }
    });

    return QueryConditionConverter;
});
