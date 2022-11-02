import DateFilterTranslatorFromExpression from './date-filter-translator';

const DATE_PART_MAP = DateFilterTranslatorFromExpression.PART_MAP;

/**
 * @inheritDoc
 */
class DatetimeFilterTranslatorFromExpression extends DateFilterTranslatorFromExpression {
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

export default DatetimeFilterTranslatorFromExpression;
