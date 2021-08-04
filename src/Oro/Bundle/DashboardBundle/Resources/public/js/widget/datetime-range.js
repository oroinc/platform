define(function(require) {
    'use strict';

    const DateTimeFilter = require('oro/filter/datetime-filter');
    const tools = require('oroui/js/tools');

    const WidgetConfigDateTimeRangeFilter = DateTimeFilter.extend({
        /**
         * @inheritdoc
         */
        events: {
            'change .datetime-visual-element': '_onClickUpdateCriteria'
        },

        /**
         * @inheritdoc
         */
        autoUpdateRangeFilterType: false,

        /**
         * @inheritdoc
         */
        constructor: function WidgetConfigDateTimeRangeFilter(options) {
            WidgetConfigDateTimeRangeFilter.__super__.constructor.call(this, options);
        },

        /**
         * Render filter view
         * Update value after render
         *
         * @return {*}
         */
        render: function() {
            WidgetConfigDateTimeRangeFilter.__super__.render.call(this);
            this.setValue(this.value);
            return this;
        },

        /**
         * @inheritdoc
         */
        _triggerUpdate: function(newValue, oldValue) {
            if (!tools.isEqualsLoosely(newValue, oldValue)) {
                this.trigger('update');
            }
        },

        /**
         * Update value without triggering events
         *
         * @param value
         */
        updateValue: function(value) {
            this.value = tools.deepClone(value);
        },

        /**
         * @inheritdoc
         */
        _updateDOMValue: function() {
            return this._writeDOMValue(this._formatRawValue(this.getValue()));
        }
    });

    return WidgetConfigDateTimeRangeFilter;
});
