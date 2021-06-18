define(function(require) {
    'use strict';

    const _ = require('underscore');
    const DatePickerView = require('./datepicker-view');
    const dateTimePickerViewMixin = require('./datetimepicker-view-mixin');

    const DateTimePickerView = DatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
        /**
         * Default options
         */
        defaults: _.extend({}, DatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

        /**
         * @inheritdoc
         */
        constructor: function DateTimePickerView(options) {
            DateTimePickerView.__super__.constructor.call(this, options);
        },

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
