import $ from 'jquery';
import FilterDatePickerView from 'orofilter/js/app/views/datepicker/filter-datepicker-view';
import dateTimePickerViewMixin from 'oroui/js/app/views/datepicker/datetimepicker-view-mixin';

const FilterDateTimePickerView = FilterDatePickerView.extend(Object.assign({}, dateTimePickerViewMixin, {
    /**
     * Default options
     */
    defaults: $.extend(true, {}, FilterDatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

    /**
     * @inheritdoc
     */
    constructor: function FilterDateTimePickerView(options) {
        FilterDateTimePickerView.__super__.constructor.call(this, options);
    },

    /**
     * Returns supper prototype for datetime picker view mixin
     *
     * @returns {Object}
     * @final
     * @protected
     */
    _super: function() {
        return FilterDatePickerView.__super__;
    }
}));

export default FilterDateTimePickerView;
