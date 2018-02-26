define(function(require) {
    'use strict';

    var WidgetDatetimeRangeView;
    var WidgetConfigDateTimeRangeFilter = require('orodashboard/js/widget/datetime-range');
    var AbstractWidgetDateRangeView = require('orodashboard/js/app/views/abstract-widget-date-range-view');

    WidgetDatetimeRangeView = AbstractWidgetDateRangeView.extend({
        /**
         * @inheritDoc
         */
        constructor: function WidgetDatetimeRangeView() {
            WidgetDatetimeRangeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            WidgetDatetimeRangeView.__super__.initialize.apply(this, arguments);

            var DatetimeFilterWithMeta = WidgetConfigDateTimeRangeFilter.extend(this.metadata);
            var dateRangeFilter = new DatetimeFilterWithMeta();
            var $dateRangeFilter = this._renderDateRangeFilter(dateRangeFilter);

            this.$('.datetime-range-filter-' + options.formName).append($dateRangeFilter);
            this._dateRangeFilterAfterRender();
        }
    });

    return WidgetDatetimeRangeView;
});
