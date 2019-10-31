define(function(require) {
    'use strict';

    const moment = require('moment');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const datetimeFormatter = require('orolocale/js/formatter/datetime');
    const defaultParam = {
        message: 'oro.form.datetime.invalid',
        maxMessage: 'oro.form.datetime.max',
        minMessage: 'oro.form.datetime.min'
    };

    /**
     * Check if datetime fits the range, then returns true, if not - returns one of numbers -1 0 1
     *  -1 - less than minimum
     *   0 - not exact as minimum or maximum (while min and max are equal)
     *   1 - greater than maximum
     *
     * @param {moment} valueMoment
     * @param {string|undefined} min
     * @param {string|undefined} max
     * @returns {boolean|number}
     */
    function between(valueMoment, min, max) {
        const format = datetimeFormatter.getBackendDateTimeFormat();
        let result = true;
        if (!_.isUndefined(min) && min === max) {
            result = moment(min, format).diff(valueMoment) === 0 || 0;
        } else {
            if (!_.isUndefined(min) && min !== null) {
                result = moment(min, format).diff(valueMoment) <= 0 || -1;
            }
            if (result === true && !_.isUndefined(max) && max !== null) {
                result = moment(max, format).diff(valueMoment) >= 0 || 1;
            }
        }
        return result;
    }

    /**
     * @export oroform/js/validator/datetime
     */
    return [
        'DateTime',
        function(value, element, param) {
            const format = element.getAttribute('data-format') === 'backend'
                ? datetimeFormatter.getBackendDateTimeFormat() : datetimeFormatter.getDateTimeFormat();
            const valueMoment = moment(String(value), format, true);
            return this.optional(element) ||
                valueMoment.isValid() && between(valueMoment, param.min, param.max) === true;
        },
        function(param, element) {
            let message;
            let datetime;
            const value = this.elementValue(element);
            const format = element.getAttribute('data-format') === 'backend'
                ? datetimeFormatter.getBackendDateTimeFormat() : datetimeFormatter.getDateTimeFormat();
            const valueMoment = moment(String(value), format, true);
            const placeholders = {};
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;

            if (!valueMoment.isValid()) {
                message = param.message;
            } else {
                switch (between(valueMoment, param.min, param.max)) {
                    case 0:
                        message = param.exactMessage;
                        datetime = param.min;
                        break;
                    case 1:
                        message = param.maxMessage;
                        datetime = param.max;
                        break;
                    case -1:
                        message = param.minMessage;
                        datetime = param.min;
                        break;
                    default:
                        return '';
                }
                placeholders.limit = datetimeFormatter.formatDateTimeNBSP(datetime);
            }

            return __(message, placeholders);
        }
    ];
});
