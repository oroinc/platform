define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');

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

    return AbstractWidgetDateCompareView;
});
