/*global define*/
define(function (require) {
    'use strict';

    var VariableDateTimePickerView, prototype,
        _ = require('underscore'),
        moment = require('moment'),
        datetimeFormatter = require('orolocale/js/formatter/datetime'),
        VariableDatePickerView = require('./variable-datepicker-view'),
        TimePickerViewPrototype = require('oroui/js/app/views/datepicker/timepicker-view-prototype');

    VariableDateTimePickerView = VariableDatePickerView.extend(_.extend({}, TimePickerViewPrototype, {
        /**
         * Default options
         */
        defaults: _.extend({}, VariableDatePickerView.prototype.defaults, TimePickerViewPrototype.defaults),

        /**
         * It's possible to use custom backend format
         */
        backendDatetimeFormat: datetimeFormatter.backendFormats.datetime,

        /**
         * Returns supper prototype
         *
         * @returns {Object}
         * @protected
         */
        _super: function () {
            return VariableDateTimePickerView.__super__;
        },

        /**
         * Initializes view
         *
         * @param {Object} options
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['backendDatetimeFormat']));
            VariableDateTimePickerView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Updates state of time field
         *  - hides/shows the field, depending on whether date has variable value or not
         */
        updateTimeFieldState: function () {
            var value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                this.$frontTimeField.val('').attr('disabled','disabled');
            } else {
                this.$frontTimeField.removeAttr('disabled');
            }
        },

        /**
         * Reads value of front fields and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function () {
            var value, date, time, momentInstance;
            date = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(date)) {
                value = this.dateVariableHelper.formatRawValue(date);
            } else {
                time = this.$frontTimeField.val();
                value = date + datetimeFormatter.getDateTimeFormatSeparator() + time;
                if (datetimeFormatter.isDateTimeValid(value)) {
                    momentInstance = datetimeFormatter.getMomentForBackendDateTime(value);
                    value = momentInstance.format(this.backendDatetimeFormat);
                } else {
                    value = '';
                }
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
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
            } else if (datetimeFormatter.isDateTimeValid(value)) {
                // if datetime in frontend format
                momentInstance = moment(value, datetimeFormatter.getDateTimeFormat(), true);
                value = momentInstance.format(datetimeFormatter.getDateFormat());
            } else if (datetimeFormatter.isValueValid(value, this.backendDatetimeFormat)) {
                // if datetime in backend format
                momentInstance = moment(value, this.backendDatetimeFormat, true);
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
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = '';
            } else if (datetimeFormatter.isDateTimeValid(value)) {
                // if datetime in frontend format
                momentInstance = moment(value, datetimeFormatter.getDateTimeFormat(), true);
                value = momentInstance.format(datetimeFormatter.getTimeFormat());
            } else if (datetimeFormatter.isValueValid(value, this.backendDatetimeFormat)) {
                // if datetime in backend format
                momentInstance = moment(value, this.backendDatetimeFormat, true);
                value = momentInstance.format(datetimeFormatter.getTimeFormat());
            }
            return value;
        }
    }));

    return VariableDateTimePickerView;
});
