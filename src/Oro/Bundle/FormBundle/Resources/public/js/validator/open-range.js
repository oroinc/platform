define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const numberFormatter = require('orolocale/js/formatter/number');

    const defaultParam = {
        minMessage: 'This value should be more than {{ limit }}.',
        maxMessage: 'This value should be less than {{ limit }}.',
        invalidMessage: 'This value should be a valid number.'
    };

    /**
     * @export oroform/js/validator/open-range
     */
    return [
        'OpenRange',
        function(value, element, param) {
            value = numberFormatter.unformat(value);
            return this.optional(element) ||
                !(isNaN(value) ||
                    (param.min !== null && value <= Number(param.min)) ||
                    (param.max !== null && value >= Number(param.max)));
        },
        function(param, element) {
            let message;
            const placeholders = {};
            const value = this.elementValue(element);
            const normalizedValue = numberFormatter.unformat(value);
            param = Object.assign({}, defaultParam, param);
            if (isNaN(normalizedValue)) {
                message = param.invalidMessage;
            } else if (param.min !== null && normalizedValue <= Number(param.min)) {
                message = param.minMessage;
                placeholders.limit = param.min;
            } else if (param.max !== null && normalizedValue >= Number(param.max)) {
                message = param.maxMessage;
                placeholders.limit = param.max;
            }
            placeholders.value = value;
            return __(message, placeholders);
        }
    ];
});
