define(function(require) {
    'use strict';

    const WidgetConfigDateTimeRangeFilter = require('orodashboard/js/widget/datetime-range');
    const AbstractWidgetDateRangeView = require('orodashboard/js/app/views/abstract-widget-date-range-view');

    const WidgetDatetimeRangeView = AbstractWidgetDateRangeView.extend({
        /**
         * @inheritdoc
         */
        constructor: function WidgetDatetimeRangeView(options) {
            WidgetDatetimeRangeView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WidgetDatetimeRangeView.__super__.initialize.call(this, options);

            const DatetimeFilterWithMeta = WidgetConfigDateTimeRangeFilter.extend(this.metadata);
            const dateRangeFilter = new DatetimeFilterWithMeta();
            const $dateRangeFilter = this._renderDateRangeFilter(dateRangeFilter);

            this.$('.datetime-range-filter-' + options.formName).append($dateRangeFilter);
            this._dateRangeFilterAfterRender();
        }
    });

    return WidgetDatetimeRangeView;
});
