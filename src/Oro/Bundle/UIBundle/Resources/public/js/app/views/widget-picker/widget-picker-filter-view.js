define(function(require) {
    'use strict';

    var  WidgetPickerFilterView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetPickerFilterView = BaseView.extend({
        template: require('tpl!oroui/templates/widget-picker/widget-picker-filter-view.html'),
        autoRender: true,
        events: {
            'keyup [data-role="widget-picker-search"]': 'onSearch',
            'change [data-role="widget-picker-search"]': 'onSearch',
            'paste [data-role="widget-picker-search"]': 'onSearch'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.onSearch = _.debounce(this.onSearch, 100);
            WidgetPickerFilterView.__super__.initialize.apply(this, arguments);
        },

        /**
         *
         * @param {Event} e
         */
        onSearch: function(e) {
            this.model.set('search', e.currentTarget.value);
        }
    });

    return WidgetPickerFilterView;
});
