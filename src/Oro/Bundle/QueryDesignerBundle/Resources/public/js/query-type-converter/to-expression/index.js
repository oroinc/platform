import AbstractFilterTranslatorToExpression from './abstract-filter-translator';
import BooleanFilterTranslatorToExpression from './boolean-filter-translator';
import DateFilterTranslatorToExpression from './date-filter-translator';
import DatetimeFilterTranslatorToExpression from './datetime-filter-translator';
import DictionaryFilterTranslatorToExpression from './dictionary-filter-translator';
import NumberFilterTranslatorToExpression from './number-filter-translator';
import StringFilterTranslatorToExpression from './string-filter-translator';

import AbstractConditionTranslatorToExpression from './abstract-condition-translator';
import FieldConditionTranslatorToExpression from './field-condition-translator';

const filterToExpression = [
    AbstractFilterTranslatorToExpression,
    BooleanFilterTranslatorToExpression,
    DateFilterTranslatorToExpression,
    DatetimeFilterTranslatorToExpression,
    DictionaryFilterTranslatorToExpression,
    NumberFilterTranslatorToExpression,
    StringFilterTranslatorToExpression
];

const conditionToExpression = [
    AbstractConditionTranslatorToExpression,
    FieldConditionTranslatorToExpression
];

export {
    filterToExpression,
    conditionToExpression
};
