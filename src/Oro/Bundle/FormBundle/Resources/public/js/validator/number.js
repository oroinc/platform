define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const localeSettings = require('orolocale/js/locale-settings');

    const defaultParam = {
        notNumberMessage: 'oro.form.number.nan',
        exactMessage: 'oro.form.number.exect',
        maxMessage: 'oro.form.number.max',
        minMessage: 'oro.form.number.min'
    };

    /**
     * Check if number fits the range, then returns true, if not - returns one of numbers -1 0 1
     *  -1 - less than minimum
     *   0 - not exact as minimum or maximum (while min and max are equal)
     *   1 - greater than maximum
     *
     * @param {number} number
     * @param {number|undefined} min
     * @param {number|undefined} max
     * @returns {boolean|number}
     */
    function between(number, min, max) {
        let result = true;
        if (min !== void 0 && min === max) {
            result = number === parseInt(min, 10) || 0;
        } else {
            if (min !== void 0 && min !== null) {
                result = number >= parseInt(min, 10) || -1;
            }
            if (result === true && max !== void 0 && max !== null) {
                result = number <= parseInt(max, 10) || 1;
            }
        }
        return result;
    }
    function toNumber(value) {
        const numberFormats = localeSettings.getNumberFormats('decimal');
        value = String(value).split(numberFormats.grouping_separator_symbol).join('');
        value = value.replace(numberFormats.decimal_separator_symbol, '.');
        return Number(value);
    }

    /**
     * @export oroform/js/validator/number
     */
    return [
        'Number',
        function(value, element, param) {
            value = toNumber(value);
            return !isNaN(value) && between(value, param.min, param.max) === true;
        },
        function(param, element, value, placeholders) {
            let message;
            let number;
            param = Object.assign({}, defaultParam, param);
            value = value === void 0 ? this.elementValue(element) : value;
            value = toNumber(value);
            if (isNaN(value)) {
                return __(param.notNumberMessage);
            } else {
                switch (between(value, param.min, param.max)) {
                    case 0:
                        message = param.exactMessage;
                        number = param.min;
                        break;
                    case 1:
                        message = param.maxMessage;
                        number = param.max;
                        break;
                    case -1:
                        message = param.minMessage;
                        number = param.min;
                        break;
                    default:
                        return '';
                }
                if (placeholders === void 0) {
                    placeholders = {};
                }
                placeholders.limit = number;
                if (placeholders.value === void 0) {
                    placeholders.value = value;
                }
                return __(message, placeholders, number);
            }
        }
    ];
});
