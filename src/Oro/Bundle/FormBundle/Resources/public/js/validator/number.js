define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var localeSettings = require('orolocale/js/locale-settings');

    var defaultParam = {
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
        var result = true;
        if (!_.isUndefined(min) && min === max) {
            result = number === parseInt(min, 10) || 0;
        } else {
            if (!_.isUndefined(min) && min !== null) {
                result = number >= parseInt(min, 10) || -1;
            }
            if (result === true && !_.isUndefined(max) && max !== null) {
                result = number <= parseInt(max, 10) || 1;
            }
        }
        return result;
    }
    function toNumber(value) {
        var numberFormats = localeSettings.getNumberFormats('decimal');
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
            var message;
            var number;
            param = _.extend({}, defaultParam, param);
            value = _.isUndefined(value) ? this.elementValue(element) : value;
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
                if (_.isUndefined(placeholders)) {
                    placeholders = {};
                }
                placeholders.limit = number;
                if (_.isUndefined(placeholders.value)) {
                    placeholders.value = value;
                }
                return __(message, placeholders, number);
            }
        }
    ];
});
