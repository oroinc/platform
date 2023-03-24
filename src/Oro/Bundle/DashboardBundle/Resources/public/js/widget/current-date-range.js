define(function(require) {
    'use strict';

    const $ = require('jquery');
    const WidgetConfigDateRangeFilter = require('orodashboard/js/widget/date-range');

    const CurrentDateWidgetConfigDateRangeFilter = WidgetConfigDateRangeFilter.extend({
        /**
         * Array of fields data which depends on current date range type
         *
         * Example: {
         *      12: {
         *          'select[name$="['dateRange2'][type]"]': 0,
         *          'select[name$="['dateRange3'][type]"]': 3
         *      }
         * }
         *
         * @property {Object}
         */
        dependentDateRangeFields: {},

        /**
         * @inheritdoc
         */
        constructor: function CurrentDateWidgetConfigDateRangeFilter(options) {
            CurrentDateWidgetConfigDateRangeFilter.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            CurrentDateWidgetConfigDateRangeFilter.__super__.initialize.call(this, options);
        },

        /**
         * - Renders filter view
         * - Updates value after render
         * - Updates related field values, if needed
         *
         * @return {*}
         */
        render: function() {
            CurrentDateWidgetConfigDateRangeFilter.__super__.render.call(this);
            this.toggleRelatedFields(this.value.type);

            return this;
        },

        /**
         * @param {$.Event} e
         */
        onChangeFilterType: function(e) {
            const select = this.$el.find(e.currentTarget);
            const value = select.val();
            this.changeFilterType(value);
            this.toggleRelatedFields(value);
        },

        /**
         * - Sets default value to the related date range fields
         * - Marks related date range fields as readonly fields
         *
         * @param value
         */
        toggleRelatedFields: function(value) {
            for (const [currentDateRangeType, relatedDateRangesData] of Object.entries(this.dependentDateRangeFields)) {
                const isDisableRelatedFields = parseInt(value) === parseInt(currentDateRangeType);

                for (const [relatedRangeSelector, relatedRangeValue] of Object.entries(relatedDateRangesData)) {
                    if (isDisableRelatedFields) {
                        $(relatedRangeSelector).val(relatedRangeValue).change();
                    }

                    $(relatedRangeSelector)
                        .attr('readonly', isDisableRelatedFields)
                        .inputWidget('refresh');
                }
            }
        }
    });

    return CurrentDateWidgetConfigDateRangeFilter;
});
