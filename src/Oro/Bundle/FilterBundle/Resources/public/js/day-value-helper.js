define(function(require) {
    'use strict';

    var _ = require('underscore');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');

    /**
     * DayValueHelper provides validation passed value and formatting it into Display/Raw formats
     *
     * @deprecated use DateValueHelper instead
     * @param {Array|string} dayFormats list of day formats or single day format (first format is considered as main)
     * @constructor
     */
    function DayValueHelper(dayFormats) {
        dayFormats = dayFormats || [datetimeFormatter.getDayFormat()];
        this.dayFormats = _.isArray(dayFormats) ? dayFormats : [dayFormats];
        this.dayFormats.push(datetimeFormatter.getBackendDayFormat());
    }

    DayValueHelper.prototype = {
        /**
         * Check if passed value is a day (match any day format)
         *
         * @param {string} value
         * @returns {boolean}
         */
        isDayValue: function(value) {
            return moment(value, this.dayFormats, true).isValid();
        },

        /**
         * Converts passed into Display value
         *
         * @param {string} value
         * @returns {string}
         */
        formatDisplayValue: function(value) {
            return moment(value, this.dayFormats, true).format(this.dayFormats[0]);
        },

        /**
         * Converts passed value into Display value
         *
         * @param {string} value
         * @returns {string}
         */
        formatRawValue: function(value) {
            return moment(value, this.dayFormats, true).format(datetimeFormatter.getBackendDayFormat());
        }
    };

    return DayValueHelper;
});
