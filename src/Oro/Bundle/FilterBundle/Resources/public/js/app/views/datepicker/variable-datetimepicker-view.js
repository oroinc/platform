define(function(require) {
    'use strict';

    var VariableDateTimePickerView;
    var _ = require('underscore');
    var VariableDatePickerView = require('./variable-datepicker-view');
    var dateTimePickerViewMixin = require('oroui/js/app/views/datepicker/datetimepicker-view-mixin');
    var moment = require('moment');

    VariableDateTimePickerView = VariableDatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: _.extend({}, VariableDatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

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
            var value = this.$el.val();
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
            dateTimePickerViewMixin.checkConsistency.apply(this, arguments);
            VariableDateTimePickerView.__super__.checkConsistency.apply(this, arguments);

            var date = this.$frontDateField.val();
            var time = this.$frontTimeField.val();

            if (!this._preventFrontendUpdate && !target && !this._isDateTimeValid(date, time)) {
                this.$frontDateField.val('');
                this.$frontTimeField.val('');
            }
        },

        _isDateTimeValid: function(date, time) {
            var part = this.$variables.dateVariables('getPart');
            var validator = this.partsDateTimeValidation[part];
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
            var value = this.$frontDateField.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatRawValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value) ?
                    this.dateValueHelper.formatRawValue(value) :
                    dateTimePickerViewMixin.getBackendFormattedValue.call(this);
            }

            return this.getBackendPartFormattedValue();
        },

        /**
         * Reads value of original field and converts it to frontend format
         *
         * @returns {string}
         */
        getFrontendFormattedDate: function() {
            var value = this.$el.val();
            if (this.dateVariableHelper.isDateVariable(value)) {
                return this.dateVariableHelper.formatDisplayValue(value);
            }

            if (this.$variables.dateVariables('getPart') === 'value') {
                return this.dateValueHelper.isValid(value) ?
                    this.dateValueHelper.formatDisplayValue(value) :
                    dateTimePickerViewMixin.getFrontendFormattedDate.call(this);
            }

            return this.getFrontendPartFormattedDate();
        }
    }));

    return VariableDateTimePickerView;
});
