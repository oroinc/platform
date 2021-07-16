define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const numberFormatter = require('orolocale/js/formatter/number');
    const defaultParam = {
        minMessage: 'This value should be {{ limit }} or more.',
        maxMessage: 'This value should be {{ limit }} or less.',
        notInRangeMessage: 'This value should be between {{ min }} and {{ max }}.',
        invalidMessage: 'This value should be a valid number.'
    };

    /**
     * @export oroform/js/validator/range
     */
    /* TODO: currently this validator support only numeric range. To consistence with backend validator
            it needs to support DateTime range as well
     */
    return [
        'Range',
        function(value, element, param) {
            value = numberFormatter.unformatStrict(value);
            return this.optional(element) ||
                !(isNaN(value) ||
                    (param.min !== null && value < numberFormatter.unformatStrict(param.min)) ||
                    (param.max !== null && value > numberFormatter.unformatStrict(param.max)));
        },
        function(param, element) {
            let message;
            const placeholders = {};
            const value = this.elementValue(element);
            const normalizedValue = numberFormatter.unformatStrict(value);
            const normalizedMin = numberFormatter.unformatStrict(param.min);
            const normalizedMax = numberFormatter.unformatStrict(param.max);
            param = Object.assign({}, defaultParam, param);
            if (isNaN(normalizedValue)) {
                message = param.invalidMessage;
            } else if (param.max !== null && param.min !== null &&
                (normalizedValue < normalizedMin || normalizedValue > normalizedMax)) {
                message = param.notInRangeMessage;
                placeholders.max = numberFormatter.formatDecimal(param.max);
                placeholders.min = numberFormatter.formatDecimal(param.min);
            } else if (param.min !== null && normalizedValue < normalizedMin) {
                message = param.minMessage;
                placeholders.limit = numberFormatter.formatDecimal(param.min);
            } else if (param.max !== null && normalizedValue > normalizedMax) {
                message = param.maxMessage;
                placeholders.limit = numberFormatter.formatDecimal(param.max);
            }
            placeholders.value = numberFormatter.formatDecimal(value);
            return __(message, placeholders);
        }
    ];
});
