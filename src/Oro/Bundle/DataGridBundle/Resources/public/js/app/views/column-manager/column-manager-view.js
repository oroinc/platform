define(function(require) {
    'use strict';

    var ColumnManagerView;
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnManagerView = BaseView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager.html'),
        autoRender: true,
        className: 'dropdown-menu'
    });

    return ColumnManagerView;
});
