define(function (require) {
    'use strict';

    var DateTimePickerView,
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        DatePickerView = require('./datepicker-view'),
        TimePickerViewPrototype = require('./timepicker-view-prototype');

    DateTimePickerView = DatePickerView.extend(_.extend({}, TimePickerViewPrototype, {
        /**
         * Default options
         */
        defaults: _.extend({}, DatePickerView.prototype.defaults, TimePickerViewPrototype.defaults),

        /**
         * Returns supper prototype
         *
         * @returns {Object}
         * @protected
         */
        _super: function () {
            return DateTimePickerView.__super__;
        },

        /**
         * Reads value of front fields and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function () {
            var value, date, time;
            date = this.$frontDateField.val();
            time = this.$frontTimeField.val();
            value = date + datetimeFormatter.getDateTimeFormatSeparator() + time;
            if (datetimeFormatter.isDateTimeValid(value)) {
                value = datetimeFormatter.convertDateTimeToBackendFormat(value);
            } else {
                value = '';
            }
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function () {
            var momentInstance,
                value = this.$el.val();
            if (datetimeFormatter.isBackendDateTimeValid(value)) {
                momentInstance = datetimeFormatter.getMomentForBackendDateTime(value);
                value = momentInstance.format(datetimeFormatter.getDateFormat());
            }
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedTime: function () {
            var momentInstance,
                value = this.$el.val();
            if (datetimeFormatter.isBackendDateTimeValid(value)) {
                momentInstance = datetimeFormatter.getMomentForBackendDateTime(value);
                value = momentInstance.format(datetimeFormatter.getTimeFormat());
            }
            return value;
        }
    }));

    return DateTimePickerView;
});
