define(function(require) {
    'use strict';

    var WidgetConfigDateTimeRangeFilter;
    var DateTimeFilter = require('oro/filter/datetime-filter');
    var tools = require('oroui/js/tools');

    WidgetConfigDateTimeRangeFilter = DateTimeFilter.extend({
        /**
         * @inheritDoc
         */
        events: {
            'change .datetime-visual-element': '_onClickUpdateCriteria'
        },

        /**
         * @inheritDoc
         */
        autoUpdateRangeFilterType: false,

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
         * @inheritDoc
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
         * @inheritDoc
         */
        _updateDOMValue: function() {
            return this._writeDOMValue(this._formatRawValue(this.getValue()));
        },
    });

    return WidgetConfigDateTimeRangeFilter;
});
