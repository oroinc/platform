import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';

const AbstractWidgetDateCompareView = BaseView.extend({
    autoRender: true,

    optionNames: BaseView.prototype.optionNames.concat([
        'formName', 'formFullName', 'valueType', 'valueConfig', 'metadata'
    ]),

    valueConfig: {start: '', end: ''},

    /**
     * @inheritdoc
     */
    constructor: function AbstractWidgetDateCompareView(options) {
        AbstractWidgetDateCompareView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.valueConfig = _.clone(this.valueConfig);

        AbstractWidgetDateCompareView.__super__.initialize.call(this, options);
    },

    _getDataRangeFilterValue: function() {
        this.valueConfig.startEndPrefix = this.formFullName;

        return {
            part: 'value',
            type: parseInt(this.valueType),
            value: this.valueConfig
        };
    },

    _renderDateRangeFilter: function(dateRangeFilter) {
        const value = this._getDataRangeFilterValue();

        dateRangeFilter.updateValue(value);
        dateRangeFilter.render();

        return dateRangeFilter.$el;
    },

    _dateRangeFilterAfterRender: function() {
        this.$el.parent().not('.ui-widget-content').css('overflow', 'inherit');
        this.$el.closest('.ui-widget-content').trigger('dialogresize');
    }
});

export default AbstractWidgetDateCompareView;
