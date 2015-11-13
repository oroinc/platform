define(function(require) {
    'use strict';

    var ColumnManagerFilterView;
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnManagerFilterView = BaseView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager-filter.html'),
        autoRender: true,
        events: {
            'click [data-role="column-manager-show-all"]': 'onShowAll',
            'click [data-role="column-manager-show-selected"]': 'onShowSelected'
        },

        listen: {
            'change model': 'render'
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
