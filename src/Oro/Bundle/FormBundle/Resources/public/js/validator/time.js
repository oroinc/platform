define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');

    const defaultParam = {
        message: 'This value is not a valid time.'
    };

    /**
     * @export oroform/js/validator/datetime
     */
    return [
        'Time',
        function(value, element) {
            const format = element.getAttribute('data-format');
            return this.optional(element) || element.type === 'time' ||
                datetimeFormatter.isTimeValid(String(value)) ||
                format === 'backend' && datetimeFormatter.isBackendTimeValid(String(value));
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
