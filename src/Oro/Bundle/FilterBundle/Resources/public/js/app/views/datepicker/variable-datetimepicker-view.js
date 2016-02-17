define(function(require) {
    'use strict';

    var VariableDateTimePickerView;
    var _ = require('underscore');
    var VariableDatePickerView = require('./variable-datepicker-view');
    var dateTimePickerViewMixin = require('oroui/js/app/views/datepicker/datetimepicker-view-mixin');
    var moment = require('moment');

    function isBetween(value, min, max) {
        value = parseInt(value);

        return !_.isNaN(value) && value >= min && value <= max;
    }

    VariableDateTimePickerView = VariableDatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: _.extend({}, VariableDatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

        partsValidation: {
            value: function (date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || (moment(date, this.getDateFormat(), true).isValid()
                        && moment(time, this.getTimeFormat(), true).isValid());
            },
            dayofweek: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || isBetween(date, 1, 7);
            },
            week: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || isBetween(date, 1, 53);
            },
            day: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || isBetween(date, 1, 31);
            },
            month: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || isBetween(date, 1, 12);
            },
            quarter: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || isBetween(date, 1, 4);
            },
            dayofyear: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || isBetween(date, 1, 365);
            },
            year: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date)
                    || !_.isNaN(parseInt(date));
            }
        },

        /**
         * Returns supper prototype for datetime picker view mixin
         *
         * @returns {Object}
         * @final
         * @protected
         */
        _super: function() {
            return VariableDateTimePickerView.__super__;
        },

        /**
         * Updates state of time field
         *  - hides/shows the field, depending on whether date has variable value or not
         */
        updateTimeFieldState: function() {
            var value = this.$el.val();
            if ((!this.$variables || this.$variables.dateVariables('getPart') !== 'value') ||
                this.dateVariableHelper.isDateVariable(value)
            ) {
                this.$frontTimeField.val('').attr('disabled', 'disabled');
            } else {
                this.$frontTimeField.removeAttr('disabled');
            }
        },

        /**
         * Check if both frontend fields (date && time) have consistent value
         *
         * @param target
         */
        checkConsistency: function(target) {
            dateTimePickerViewMixin.checkConsistency.apply(this, arguments);

            var date = this.$frontDateField.val();
            var time = this.$frontTimeField.val();

            if (!target && !this._isValid(date, time)) {
                this.$frontDateField.val('');
                this.$frontTimeField.val('');
            }
        },

        _isValid: function(date, time) {
            var part = this.$variables.dateVariables('getPart');
            var validator = this.partsValidation[part];
            if (!validator) {
                return false;
            }

            return validator.call(this, date, time);
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            var value = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatRawValue(value);
            } else {
                value = dateTimePickerViewMixin.getBackendFormattedValue.call(this);
            }
            return value;
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function() {
            var value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                value = this.dateVariableHelper.formatDisplayValue(value);
            } else {
                value = dateTimePickerViewMixin.getFrontendFormattedDate.call(this);
            }
            return value;
        }
    }));

    return VariableDateTimePickerView;
});
