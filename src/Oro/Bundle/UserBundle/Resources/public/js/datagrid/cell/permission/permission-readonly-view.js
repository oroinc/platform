define(function(require) {
    'use strict';

    var PermissionReadOnlyView;
    var BaseView = require('oroui/js/app/views/base/view');
    var accessLevels = require('orouser/js/constants/access-levels');

    PermissionReadOnlyView = BaseView.extend({
        tagName: 'li',
        className: 'action-permissions__item dropdown',
        template: require('tpl!orouser/templates/datagrid/cell/permission/permission-readonly-view.html'),
        getTemplateData: function() {
            var data = PermissionReadOnlyView.__super__.getTemplateData.apply(this, arguments);
            data.noAccess = accessLevels.NONE === this.model.get('access_level');
            return data;
        }
    });

    return PermissionReadOnlyView;
});
