define(function(require) {
    'use strict';

    var WeekDayPickerView;
    var _ = require('underscore');
    var localeSettings = require('orolocale/js/locale-settings');
    var MultiCheckboxView = require('oroform/js/app/views/multi-checkbox-view');

    WeekDayPickerView = MultiCheckboxView.extend({
        /**
         * @inheritDoc
         */
        constructor: function WeekDayPickerView() {
            WeekDayPickerView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            var items = this.createItems();
            WeekDayPickerView.__super__.initialize.call(this, _.extend({items: items}, options));
        },

        createItems: function() {
            var keys = localeSettings.getSortedDayOfWeekNames('mnemonic');
            var texts = localeSettings.getSortedDayOfWeekNames('narrow');
            return _.map(_.object(keys, texts), function(text, key) {
                return {
                    value: key,
                    text: text
                };
            });
        }
    });

    return WeekDayPickerView;
});
