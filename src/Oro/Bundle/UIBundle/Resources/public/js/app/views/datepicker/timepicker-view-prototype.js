define(function (require) {
    'use strict';

    var TimePickerViewPrototype,
        $ = require('jquery'),
        _ = require('underscore'),
        datetimeFormatter = require('orolocale/js/formatter/datetime');
    require('oroui/lib/jquery.timepicker-1.4.13/jquery.timepicker');

    /**
     * Mixin with prototype of TimePickerView implementation
     * (is used to extend some DatePickerView with timepicker functionality)
     * @interface TimePickerView
     */
    TimePickerViewPrototype = {
        defaults: {
            fieldsWrapper: '',
            timeInputAttrs: {},
            timePickerOptions: {}
        },

        /**
         * Format of time that native date input accepts
         */
        nativeTimeFormat: 'HH:mm',

        /**
         * Format of date/datetime that original input accepts
         */
        backendFormat: datetimeFormatter.backendFormats.datetime,

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
            this._super().dispose.apply(this, arguments);
        },

        /**
         * Creates frontend field
         *
         * @param {Object} options
         */
        createFrontField: function (options) {
            this._super().createFrontField.call(this, options);
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
            this._super().initPickerWidget.apply(this, arguments);
        },

        /**
         * Destroys picker widget
         */
        destroyTimePickerWidget: function () {
            this.$frontTimeField.timepicker('remove');
        },

        /**
         * Updates original field on front field change
         */
        updateOrigin: function () {
            this._super().updateOrigin.apply(this, arguments);
            this.updateTimeFieldState();
        },

        /**
         * Update front date and time fields values
         */
        updateFront: function () {
            this._super().updateFront.call(this);
            this.$frontTimeField.val(this.getFrontendFormattedTime());
            this.updateTimeFieldState();
        },

        /**
         * Updates state of time field
         * (might be defined in the extend)
         */
        updateTimeFieldState: $.noop,

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedTime: function () {
            var value = '',
                momentInstance = this.getOriginalMoment();
            if (momentInstance) {
                value = momentInstance.format(this.getTimeFormat());
            }
            return value;
        },

        /**
         * Creates moment object for frontend field
         *
         * @returns {moment}
         */
        getFrontendMoment: function () {
            var value, date, time, format, momentInstance;
            date = this.$frontDateField.val();
            time = this.$frontTimeField.val();
            value = date + this.getSeparatorFormat() + time;
            format = this.getDateTimeFormat();
            momentInstance = moment(value, format, true);
            if (momentInstance.isValid()) {
                return momentInstance;
            }
        },

        /**
         * Defines frontend format for time field
         *
         * @returns {string}
         */
        getTimeFormat: function () {
            return this.nativeMode ? this.nativeTimeFormat : datetimeFormatter.getTimeFormat();
        },

        /**
         * Defines frontend format for datetime separator
         *
         * @returns {string}
         */
        getSeparatorFormat: function () {
            return this.nativeMode ? ' ' : datetimeFormatter.getDateTimeFormatSeparator();
        },

        /**
         * Defines frontend format for datetime field
         *
         * @returns {string}
         */
        getDateTimeFormat: function () {
            var dateFormat, timeFormat, separatorFormat;
            dateFormat = this.getDateFormat();
            timeFormat = this.getTimeFormat();
            separatorFormat = this.getSeparatorFormat();
            return dateFormat + separatorFormat + timeFormat;
        }
    };

    return TimePickerViewPrototype;
});
