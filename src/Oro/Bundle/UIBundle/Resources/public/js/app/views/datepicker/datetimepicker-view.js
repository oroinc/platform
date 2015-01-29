define(function (require) {
    'use strict';

    var DateTimePickerView,
        _ = require('underscore'),
        DatePickerView = require('./datepicker-view'),
        dateTimePickerViewMixin = require('./datetimepicker-view-mixin');

    DateTimePickerView = DatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: _.extend({}, DatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

        /**
         * Returns supper prototype
         *
         * @returns {Object}
         * @protected
         */
        _super: function () {
            return DateTimePickerView.__super__;
        }
    }));

    return DateTimePickerView;
});
