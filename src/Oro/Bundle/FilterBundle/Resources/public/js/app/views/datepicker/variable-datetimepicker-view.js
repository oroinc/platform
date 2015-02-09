/*global define*/
define(function (require) {
    'use strict';

    var VariableDateTimePickerView,
        _ = require('underscore'),
        VariableDatePickerView = require('./variable-datepicker-view'),
        dateTimePickerViewMixin = require('oroui/js/app/views/datepicker/datetimepicker-view-mixin');

    VariableDateTimePickerView = VariableDatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: _.extend({}, VariableDatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

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
         * Initializes variable-date-time-picker view
         * @param {Object} options
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['timezoneShift']));
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
         * Check if both frontend fields (date && time) have consistent value
         *
         * @param target
         */
        checkConsistency: function (target) {
            var date, time, isVariable, isValidDate, isValidTime;
            dateTimePickerViewMixin.checkConsistency.apply(this, arguments);

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
