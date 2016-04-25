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
        _formatRawValue: function(value) {
            if (value.value && value.value.start) {
                value.value.start = this._toRawValue(value.value.start);

                this._setInputValue(this.criteriaValueSelectors.value.start, value.value.start);
            }
            if (value.value && value.value.end) {
                value.value.end = this._toRawValue(value.value.end);

                this._setInputValue(this.criteriaValueSelectors.value.end, value.value.end);
            }

            return value;
        },

        /**
         * @inheritDoc
         */
        setValue: function(value) {
            var oldValue = this.value;
            this.value = tools.deepClone(value);
            this._onValueUpdated(this.value, oldValue);

            return this;
        }
    });

    return WidgetConfigDateTimeRangeFilter;
});
