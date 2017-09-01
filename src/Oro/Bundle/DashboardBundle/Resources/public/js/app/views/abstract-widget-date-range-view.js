define(function(require) {
    'use strict';

    var AbstractWidgetDateCompareView;
    var BaseView = require('oroui/js/app/views/base/view');

    AbstractWidgetDateCompareView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'formName', 'formFullName', 'valueType', 'valueConfig', 'metadata'
        ]),

        valueConfig: {start: '', end: ''},

        _getDataRangeFilterValue: function() {
            this.valueConfig.startEndPrefix = this.formFullName;

            return {
                part: 'value',
                type: parseInt(this.valueType),
                value: this.valueConfig
            };
        },

        _renderDateRangeFilter: function(dateRangeFilter) {
            var value = this._getDataRangeFilterValue();

            dateRangeFilter.updateValue(value);
            dateRangeFilter.render();

            return dateRangeFilter.$el;
        },

        _dateRangeFilterAfterRender: function() {
            this.$el.parent().not('.ui-widget-content').css('overflow', 'inherit');
            this.$el.closest('.ui-widget-content').trigger('dialogresize');
        }
    });

    return AbstractWidgetDateCompareView;
});
