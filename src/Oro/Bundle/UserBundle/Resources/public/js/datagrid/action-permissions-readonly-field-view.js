define(function(require) {
    'use strict';

    var ActionPermissionsReadonlyFieldView;
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var PermissionReadOnlyView = require('orouser/js/datagrid/permission/permission-readonly-view');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionPermissionsReadonlyFieldView = BaseView.extend({
        autoRender: false,
        animationDuration: 0,
        className: 'field-permission-container clearfix',
        template: require('tpl!orouser/templates/datagrid/action-permissions-field-view.html'),
        permissionView: PermissionReadOnlyView,

        render: function() {
            ActionPermissionsReadonlyFieldView.__super__.render.call(this);
            this.subview('permissions-items', new BaseCollectionView({
                el: this.$('[data-name=field-permissions-items]'),
                tagName: 'ul',
                className: 'action-permissions',
                animationDuration: 0,
                collection: this.model.get('permissions'),
                itemView: this.permissionView
            }));
            return this;
        }
    });

    return ActionPermissionsReadonlyFieldView;
});
