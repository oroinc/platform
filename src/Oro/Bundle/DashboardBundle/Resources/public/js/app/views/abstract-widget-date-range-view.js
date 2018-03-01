define(function(require) {
    'use strict';

    var AbstractWidgetDateCompareView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    AbstractWidgetDateCompareView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'formName', 'formFullName', 'valueType', 'valueConfig', 'metadata'
        ]),

        valueConfig: {start: '', end: ''},

        /**
         * @inheritDoc
         */
        constructor: function AbstractWidgetDateCompareView() {
            AbstractWidgetDateCompareView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.valueConfig = _.clone(this.valueConfig);

            AbstractWidgetDateCompareView.__super__.initialize.apply(this, arguments);
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
