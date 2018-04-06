define(function(require) {
    'use strict';

    var moment = require('moment');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var defaultParam = {
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
        var format = datetimeFormatter.getBackendDateTimeFormat();
        var result = true;
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
            var format = element.getAttribute('data-format') === 'backend'
                ? datetimeFormatter.getBackendDateTimeFormat() : datetimeFormatter.getDateTimeFormat();
            var valueMoment = moment(String(value), format, true);
            return this.optional(element) ||
                valueMoment.isValid() && between(valueMoment, param.min, param.max) === true;
        },
        function(param, element) {
            var message;
            var datetime;
            var value = this.elementValue(element);
            var format = element.getAttribute('data-format') === 'backend'
                ? datetimeFormatter.getBackendDateTimeFormat() : datetimeFormatter.getDateTimeFormat();
            var valueMoment = moment(String(value), format, true);
            var placeholders = {};
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
