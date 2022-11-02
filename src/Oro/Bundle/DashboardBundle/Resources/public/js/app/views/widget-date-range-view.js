define(function(require) {
    'use strict';

    const $ = require('jquery');
    const WidgetConfigDateRangeFilter = require('orodashboard/js/widget/date-range');
    const AbstractWidgetDateRangeView = require('orodashboard/js/app/views/abstract-widget-date-range-view');

    const WidgetDateRangeView = AbstractWidgetDateRangeView.extend({
        /**
         * @inheritdoc
         */
        constructor: function WidgetDateRangeView(options) {
            WidgetDateRangeView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            WidgetDateRangeView.__super__.initialize.call(this, options);

            const DatetimeFilterWithMeta = WidgetConfigDateRangeFilter.extend(this.metadata);
            const dateRangeFilter = new DatetimeFilterWithMeta({
                $form: $('[name="' + options.formFullName + '[part]"]').closest('form')
            });
            const $dateRangeFilter = this._renderDateRangeFilter(dateRangeFilter);

            this.$('.date-range-filter-' + options.formName).append($dateRangeFilter).trigger('content:changed');
            this._dateRangeFilterAfterRender();
        }
    });

    return WidgetDateRangeView;
});
