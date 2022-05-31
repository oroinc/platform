define(function(require) {
    'use strict';

    const $ = require('jquery');
    const VariableDatePickerView = require('orofilter/js/app/views/datepicker/variable-datepicker-view');
    const dateTimePickerViewMixin = require('oroui/js/app/views/datepicker/datetimepicker-view-mixin');
    const moment = require('moment');

    const VariableDateTimePickerView = VariableDatePickerView.extend(Object.assign({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: $.extend(true, {}, VariableDatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

        partsDateTimeValidation: {
            value: function(date, time) {
                return this.dateVariableHelper.isDateVariable(date) ||
                    this.dateValueHelper.isValid(date) ||
                    (moment(date, this.getDateFormat(), true).isValid() &&
                     moment(time, this.getTimeFormat(), true).isValid());
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
            const value = this.$el.val();
            if ((!this.$variables || this.$variables.dateVariables('getPart') !== 'value') ||
                this.dateVariableHelper.isDateVariable(value) ||
                this.dateValueHelper.isValid(value)
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
            dateTimePickerViewMixin.checkConsistency.call(this, target);
            VariableDateTimePickerView.__super__.checkConsistency.call(this, target);

            const date = this.$frontDateField.val();
            const time = this.$frontTimeField.val();

            if (
                !this._preventFrontendUpdate &&
                !this.$frontDateField.is(target) &&
                !this.$frontTimeField.is(target) &&
                !this._isDateTimeValid(date, time)
            ) {
                this.$frontDateField.val('');
                this.$frontTimeField.val('');
            }
        },

        _isDateTimeValid: function(date, time) {
            const part = this.$variables.dateVariables('getPart');
            const validator = this.partsDateTimeValidation[part];
            if (!validator) {
                return true;
            }

            return validator.call(this, date, time);
        },

        /**
         * Reads value of front field and converts it to backend format
         *
         * @returns {string}
         */
        getBackendFormattedValue: function() {
            const value = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatRawValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value)
                    ? this.dateValueHelper.formatRawValue(value)
                    : VariableDateTimePickerView.__super__.getBackendFormattedValue.call(this);
            }

            return this.getBackendPartFormattedValue();
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function() {
            const value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatDisplayValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value)
                    ? this.dateValueHelper.formatDisplayValue(value)
                    : dateTimePickerViewMixin.getFrontendFormattedDate.call(this);
            }

            return this.getFrontendPartFormattedDate();
        }
    }));

    return VariableDateTimePickerView;
});
