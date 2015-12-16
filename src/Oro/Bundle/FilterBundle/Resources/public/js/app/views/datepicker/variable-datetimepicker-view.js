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
            if (this.dateVariableHelper.isDateVariable(value)) {
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
