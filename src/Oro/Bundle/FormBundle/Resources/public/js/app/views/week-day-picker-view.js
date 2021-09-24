define(function(require) {
    'use strict';

    const _ = require('underscore');
    const localeSettings = require('orolocale/js/locale-settings');
    const MultiCheckboxView = require('oroform/js/app/views/multi-checkbox-view');

    const WeekDayPickerView = MultiCheckboxView.extend({
        /**
         * @inheritdoc
         */
        constructor: function WeekDayPickerView(options) {
            WeekDayPickerView.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            const items = this.createItems();
            WeekDayPickerView.__super__.initialize.call(this, _.extend({items: items}, options));
        },

        createItems: function() {
            const keys = localeSettings.getSortedDayOfWeekNames('mnemonic');
            const texts = localeSettings.getSortedDayOfWeekNames('narrow');
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
