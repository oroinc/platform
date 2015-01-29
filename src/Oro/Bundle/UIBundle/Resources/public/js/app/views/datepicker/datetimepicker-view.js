define(function (require) {
    'use strict';

    var DateTimePickerView,
        _ = require('underscore'),
        DatePickerView = require('./datepicker-view'),
        DateTimePickerViewPrototype = require('./datetimepicker-view-prototype');

    DateTimePickerView = DatePickerView.extend(_.extend({}, DateTimePickerViewPrototype, {
        /**
         * Default options
         */
        defaults: _.extend({}, DatePickerView.prototype.defaults, DateTimePickerViewPrototype.defaults),

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
