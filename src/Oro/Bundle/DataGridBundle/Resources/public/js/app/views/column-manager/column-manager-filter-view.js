define(function(require) {
    'use strict';

    var ColumnManagerFilterView;
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnManagerFilterView = BaseView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager-filter.html'),
        autoRender: true,
        events: {
            'keyup [data-role="column-manager-search"]': 'onSearch',
            'paste [data-role="column-manager-search"]': 'onSearch',
            'click [data-role="column-manager-clear-search"]': 'onClearSearch',
            'click [data-role="column-manager-show-all"]': 'onShowAll',
            'click [data-role="column-manager-show-selected"]': 'onShowSelected'
        },

        listen: {
            'change model': 'render'
        },

        onSearch: function(e) {
            this.model.set('search', e.currentTarget.value);
        },

        onClearSearch: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.model.set('search', '');
        },

        onShowAll: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.model.set('renderable', false);
        },

        onShowSelected: function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.model.set('renderable', true);
        }
    });

    return ColumnManagerFilterView;
});
