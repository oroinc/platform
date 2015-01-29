/*global define*/
define(function (require) {
    'use strict';

    var VariableDateTimePickerView, prototype,
        _ = require('underscore'),
        VariableDatePickerView = require('./variable-datepicker-view'),
        DateTimePickerViewPrototype = require('oroui/js/app/views/datepicker/datetimepicker-view-prototype');

    VariableDateTimePickerView = VariableDatePickerView.extend(_.extend({}, DateTimePickerViewPrototype, {
        /**
         * Default options
         */
        defaults: _.extend({}, VariableDatePickerView.prototype.defaults, DateTimePickerViewPrototype.defaults),

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
         * Check if both frontend fields (date && time) have consistent value
         *
         * @param target
         */
        checkConsistency: function (target) {
            var date, time, isVariable, isValidDate, isValidTime;
            DateTimePickerViewPrototype.checkConsistency.apply(this, arguments);

            date = this.$frontDateField.val();
            time = this.$frontTimeField.val();
            isVariable = this.dateVariableHelper.isDateVariable(date);
            isValidDate = moment(date, this.getDateFormat(), true).isValid();
            isValidTime = moment(time, this.getTimeFormat(), true).isValid();

            if (!target && !isVariable && (!isValidDate || !isValidTime)) {
                this.$frontDateField.val('');
                this.$frontTimeField.val('');
            }
        }
    }));

    return VariableDateTimePickerView;
});
