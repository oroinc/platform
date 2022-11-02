import DateFilterTranslatorToExpression from './date-filter-translator';

const DATE_PART_MAP = DateFilterTranslatorToExpression.PART_MAP;

class DatetimeFilterTranslatorToExpression extends DateFilterTranslatorToExpression {
    /**
     * @inheritDoc
     */
    static TYPE = 'datetime';

    /**
     * @inheritDoc
     */
    static PART_MAP = {
        ...DATE_PART_MAP,
        value: {
            ...DATE_PART_MAP.value,
            valuePattern: /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/
        }
    };
}

export default DatetimeFilterTranslatorToExpression;
