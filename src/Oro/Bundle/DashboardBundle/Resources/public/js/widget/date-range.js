define(function(require) {
    'use strict';

    var WidgetConfigDateRangeFilter;
    var DateFilter = require('oro/filter/date-filter');
    var tools = require('oroui/js/tools');

    WidgetConfigDateRangeFilter = DateFilter.extend({
        /**
         * @inheritDoc
         */
        events: {
            'change .date-visual-element': '_onClickUpdateCriteria'
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
            WidgetConfigDateRangeFilter.__super__.render.call(this);
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

    return WidgetConfigDateRangeFilter;
});
