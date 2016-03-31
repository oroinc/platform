define([
    'underscore',
    'moment',
    'orolocale/js/formatter/datetime'
], function(_, moment, datetimeFormatter) {
    'use strict';

    function DateValueHelper(dayFormats) {
        dayFormats = dayFormats || [datetimeFormatter.getDayFormat()];
        dayFormats = _.isArray(dayFormats) ? dayFormats : [dayFormats];
        dayFormats.push(datetimeFormatter.getBackendDayFormat());

        this.backendFormats = {};
        this.backendFormats[this.DAY] = datetimeFormatter.getBackendDayFormat();
        this.backendFormats[this.MONTH] = datetimeFormatter.getBackendMonthFormat();

        this.formats = {};
        this.formats[this.DAY] = dayFormats;
        this.formats[this.MONTH] = ['MMM', 'MMMM', this.backendFormats[this.MONTH]];
    }

    DateValueHelper.prototype = {
        DAY: 'day',
        MONTH: 'month',

        isValid: function(value) {
            var type = this._valueType(value);

            return moment(value, this.formats[type], true).isValid();
        },

        formatDisplayValue: function(rawValue) {
            var type = this._valueType(rawValue);

            return moment(rawValue, this.formats[type], true).format(this.formats[type][0]);
        },

        formatRawValue: function(displayValue) {
            var type = this._valueType(displayValue);

            return moment(displayValue, this.formats[type], true).format(this.backendFormats[type]);
        },

        _valueType: function(value) {
            return moment(value, this.formats[this.DAY], true).isValid() ? this.DAY : this.MONTH;
        }
    };

    return DateValueHelper;
});
