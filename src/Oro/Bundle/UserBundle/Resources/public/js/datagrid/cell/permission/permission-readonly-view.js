define(function(require) {
    'use strict';

    var PermissionReadOnlyView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    PermissionReadOnlyView = BaseView.extend({
        ACCESS_LEVEL_NONE: 0,
        tagName: 'li',
        className: 'action-permissions__item dropdown',
        template: require('tpl!orouser/templates/datagrid/cell/permission/permission-readonly-view.html'),
        getTemplateData: function() {
            var data = PermissionReadOnlyView.__super__.getTemplateData.apply(this, arguments);
            data.noAccess = this.ACCESS_LEVEL_NONE === this.model.get('access_level');
            return data;
        }
    });

    return PermissionReadOnlyView;
});
