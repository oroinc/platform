define(function(require) {
    'use strict';

    var ActionPermissionsReadonlyFieldView;
    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var PermissionModel = require('orouser/js/models/role/permission-model');
    var PermissionReadOnlyView = require('orouser/js/datagrid/permission/permission-readonly-view');
    var BaseView = require('oroui/js/app/views/base/view');

    ActionPermissionsReadonlyFieldView = BaseView.extend({
        autoRender: false,
        animationDuration: 0,
        className: 'field-permission-container clearfix',
        template: require('tpl!orouser/templates/datagrid/action-permissions-field-view.html'),
        permissionView: PermissionReadOnlyView,

        initialize: function(options) {
            ActionPermissionsReadonlyFieldView.__super__.initialize.call(this, options);
            var permissionCollection = new BaseCollection(_.values(this.model.get('permissions')), {
                model: PermissionModel
            });
            this.model.set('permissions', permissionCollection);
        },
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
