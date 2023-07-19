import AbstractFilterTranslatorFromExpression from './abstract-filter-translator';
import BooleanFilterTranslatorFromExpression from './boolean-filter-translator';
import DateFilterTranslatorFromExpression from './date-filter-translator';
import DatetimeFilterTranslatorFromExpression from './datetime-filter-translator';
import DictionaryFilterTranslatorFromExpression from './dictionary-filter-translator';
import NumberFilterTranslatorFromExpression from './number-filter-translator';
import StringFilterTranslatorFromExpression from './string-filter-translator';

import AbstractConditionTranslatorFromExpression from './abstract-condition-translator';
import FieldConditionTranslatorFromExpression from './field-condition-translator';

const filterFromExpression = [
    AbstractFilterTranslatorFromExpression,
    BooleanFilterTranslatorFromExpression,
    DateFilterTranslatorFromExpression,
    DatetimeFilterTranslatorFromExpression,
    DictionaryFilterTranslatorFromExpression,
    NumberFilterTranslatorFromExpression,
    StringFilterTranslatorFromExpression
];

const conditionFromExpression = [
    AbstractConditionTranslatorFromExpression,
    FieldConditionTranslatorFromExpression
];

export {
    filterFromExpression,
    conditionFromExpression
};
