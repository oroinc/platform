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
        }
    }));

    return VariableDateTimePickerView;
});
