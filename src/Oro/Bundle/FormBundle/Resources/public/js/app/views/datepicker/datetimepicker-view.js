define(function (require) {
    'use strict';

    var DateTimePickerView,
        $ = require('jquery'),
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        DatePickerView = require('./datepicker-view');
    require('oroform/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    // @TODO fixed in BAP-7094
    $.fn.timepicker.defaults.timeFormat = 'g:i A';

    DateTimePickerView = DatePickerView.extend({
        defaults: {
            useNativePicker: false,
            fieldsWrapper: '',
            dateInputAttrs: {},
            datePickerOptions: {},
            timeInputAttrs: {},
            timePickerOptions: {}
        },

        /**
         * Cleans up HTML
         *  - destroys picker widget
         *  - removes front field
         *  - unwrap original field
         *
         * @override
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            if (!this.nativeMode) {
                this.destroyTimePickerWidget();
            }
            this.$frontTimeField.off().remove();
            if (this.$frontDateField.data('isWrapped')) {
                this.$frontDateField.unwrap();
            }
            DateTimePickerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Creates frontend field
         *
         * @param {Object} options
         */
        createFrontField: function (options) {
            DateTimePickerView.__super__.createFrontField.call(this, options);
            if (options.fieldsWrapper) {
                this.$frontDateField
                    .wrap(options.fieldsWrapper)
                    .data('isWrapped', true);
            }
            this.$frontTimeField = $('<input />');
            options.timeInputAttrs.type = this.nativeMode ? 'time' : 'text';
            this.$frontTimeField.attr(options.timeInputAttrs);
            this.$frontTimeField.on('keyup change', _.bind(this.updateOrigin, this));
            this.$frontDateField.after(this.$frontTimeField);
        },

        /**
         * Initializes date and time pickers widget
         *
         * @param {Object} options
         */
        initPickerWidget: function (options) {
            var widgetOptions = options.timePickerOptions;
            this.$frontTimeField.timepicker(widgetOptions);
            DateTimePickerView.__super__.initPickerWidget.apply(this, arguments);
        },

        /**
         * Destroys picker widget
         */
        destroyTimePickerWidget: function () {
            this.$frontTimeField.timepicker('remove');
        },

        /**
         * Update front date and time fields values
         */
        updateFront: function () {
            DateTimePickerView.__super__.updateFront.call(this);
            this.$frontTimeField.val(this.getFrontendFormattedTime());
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
            value = date + ' ' + time;
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
            var moment = datetimeFormatter.getMomentForBackendDateTime(this.$el.val()),
                value = moment.format(datetimeFormatter.getDateFormat());
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedTime: function () {
            var moment = datetimeFormatter.getMomentForBackendDateTime(this.$el.val()),
                value = moment.format(datetimeFormatter.getTimeFormat());
            return value;
        }
    });

    return DateTimePickerView;
});
