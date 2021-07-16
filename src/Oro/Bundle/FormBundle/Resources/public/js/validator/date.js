define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');

    const defaultParam = {
        message: 'This value is not a valid date.'
    };

    /**
     * @export oroform/js/validator/date
     */
    return [
        'Date',
        function(value, element) {
            const format = element.getAttribute('data-format');
            return this.optional(element) || element.type === 'date' ||
                datetimeFormatter.isDateValid(String(value)) ||
                format === 'backend' && datetimeFormatter.isBackendDateValid(String(value));
        },
        function(param, element) {
            const value = String(this.elementValue(element));
            const placeholders = {};
            param = Object.assign({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
