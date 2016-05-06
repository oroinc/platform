define(function(require) {
    'use strict';

    var NativeDateTimePickerView;
    var _ = require('underscore');
    var DatePickerView = require('oroui/js/app/views/datepicker/datepicker-view');
    var dateTimePickerViewMixin = require('oroui/js/app/views/datepicker/datetimepicker-view-mixin');

    NativeDateTimePickerView = DatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Returns supper prototype for datetime picker view mixin
         *
         * @returns {Object}
         * @final
         * @protected
         */
        _super: function() {
            return NativeDateTimePickerView.__super__;
        },

        /**
         * Initializes variable-date-picker view
         * @param {Object} options
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['backendFormat']));
            NativeDateTimePickerView.__super__.initialize.apply(this, arguments);
        }
    }));

    return NativeDateTimePickerView;
});
