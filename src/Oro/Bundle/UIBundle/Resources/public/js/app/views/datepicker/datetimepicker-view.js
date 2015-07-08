define(function(require) {
    'use strict';

    var DateTimePickerView;
    var _ = require('underscore');
    var DatePickerView = require('./datepicker-view');
    var dateTimePickerViewMixin = require('./datetimepicker-view-mixin');

    DateTimePickerView = DatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: _.extend({}, DatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

        /**
         * Returns supper prototype for datetime picker view mixin
         *
         * @returns {Object}
         * @final
         * @protected
         */
        _super: function() {
            return DateTimePickerView.__super__;
        }
    }));

    return DateTimePickerView;
});
