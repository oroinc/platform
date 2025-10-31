import $ from 'jquery';
import CurrentDateWidgetConfigDateRangeFilter from 'orodashboard/js/widget/current-date-range';
import WidgetDateRangeView from 'orodashboard/js/app/views/widget-date-range-view';

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

export default CurrentDateWidgetDateRangeView;
