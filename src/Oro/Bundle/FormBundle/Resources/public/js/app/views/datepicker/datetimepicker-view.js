define(function (require) {
    'use strict';

    var DateTimePickerView,
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        DatePickerView = require('./datepicker-view');
    require('jquery-ui-timepicker');

    DateTimePickerView = DatePickerView.extend({
        type: 'datetime',

        /**
         * Initializes picker widget
         *
         * @param {Object} options
         */
        initPickerWidget: function (options) {
            this.$frontField.datetimepicker(options);
        },

        /**
         * Destroys picker widget
         */
        destroyPickerWidget: function () {
            this.$frontField.datetimepicker('destroy');
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function () {
            var value = this.$frontField.val();
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
        getFrontendFormattedValue: function () {
            var value = datetimeFormatter.formatDateTime(this.$el.val());
            return value;
        }
    });

    return DateTimePickerView;
});
