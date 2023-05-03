define(function(require) {
    'use strict';

    const $ = require('jquery');
    const CurrentDateWidgetConfigDateRangeFilter = require('orodashboard/js/widget/current-date-range');
    const WidgetDateRangeView = require('orodashboard/js/app/views/widget-date-range-view');

    const CurrentDateWidgetDateRangeView = WidgetDateRangeView.extend({
        /**
         * @inheritdoc
         */
        constructor: function CurrentDateWidgetDateRangeView(options) {
            CurrentDateWidgetDateRangeView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const DatetimeFilterWithMeta = CurrentDateWidgetConfigDateRangeFilter.extend(this.metadata);
            const dateRangeFilter = new DatetimeFilterWithMeta({
                $form: $('[name="' + options.formFullName + '[part]"]').closest('form')
            });
            const $dateRangeFilter = this._renderDateRangeFilter(dateRangeFilter);

            this.$('.date-range-filter-' + options.formName).append($dateRangeFilter).trigger('content:changed');
            this._dateRangeFilterAfterRender();
        }
    });

    return CurrentDateWidgetDateRangeView;
});
