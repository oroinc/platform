import $ from 'jquery';
import DatePickerView from './datepicker-view';
import dateTimePickerViewMixin from './datetimepicker-view-mixin';

const DateTimePickerView = DatePickerView.extend(Object.assign({}, dateTimePickerViewMixin, {
    /**
     * Default options
     */
    defaults: $.extend(true, {}, DatePickerView.prototype.defaults, dateTimePickerViewMixin.defaults),

    /**
     * @inheritdoc
     */
    constructor: function DateTimePickerView(options) {
        DateTimePickerView.__super__.constructor.call(this, options);
    },

    /**
     * Returns supper prototype for datetime picker view mixin
     *
     * @returns {Object}
     * @final
     * @protected
     */
    _super: function() {
        return DateTimePickerView.__super__;
    }
}));

export default DateTimePickerView;
