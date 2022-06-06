import _ from 'underscore';
import FilterDatePickerView from 'orofilter/js/app/views/datepicker/filter-datapicker-view';
import dateTimePickerViewMixin from 'oroui/js/app/views/datepicker/datetimepicker-view-mixin';

const FilterDateTimePickerView = FilterDatePickerView.extend(_.extend({}, dateTimePickerViewMixin, {
    /**
     * Default options
     */
    defaults: _.extend({}, FilterDatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

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
