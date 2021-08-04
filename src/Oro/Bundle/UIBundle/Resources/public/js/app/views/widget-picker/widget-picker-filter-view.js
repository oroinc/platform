define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const WidgetPickerFilterView = BaseView.extend({
        template: require('tpl-loader!oroui/templates/widget-picker/widget-picker-filter-view.html'),

        autoRender: true,

        events: {
            'input [data-role="filter-search"]': 'onSearchChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerFilterView(options) {
            WidgetPickerFilterView.__super__.constructor.call(this, options);
        },

        /**
         * Handles search input value change and update the model
         *
         * @param {Event} e
         */
        onSearchChange: function(e) {
            this.model.set('search', e.currentTarget.value);
        }
    });

    return WidgetPickerFilterView;
});
