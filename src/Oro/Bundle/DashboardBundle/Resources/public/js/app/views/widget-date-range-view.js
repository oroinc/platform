define(function(require) {
    'use strict';

    var WidgetDateRangeView;
    var $ = require('jquery');
    var WidgetConfigDateRangeFilter = require('orodashboard/js/widget/date-range');
    var AbstractWidgetDateRangeView = require('orodashboard/js/app/views/abstract-widget-date-range-view');

    WidgetDateRangeView = AbstractWidgetDateRangeView.extend({
        /**
         * @inheritDoc
         */
        constructor: function WidgetDateRangeView() {
            WidgetDateRangeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            WidgetDateRangeView.__super__.initialize.apply(this, arguments);

            var DatetimeFilterWithMeta = WidgetConfigDateRangeFilter.extend(this.metadata);
            var dateRangeFilter = new DatetimeFilterWithMeta({
                $form: $('[name="' + options.formFullName + '[part]"]').closest('form')
            });
            var $dateRangeFilter = this._renderDateRangeFilter(dateRangeFilter);

            this.$('.date-range-filter-' + options.formName).append($dateRangeFilter).trigger('content:changed');
            this._dateRangeFilterAfterRender();
        }
    });

    return WidgetDateRangeView;
});
