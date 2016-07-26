define(function(require) {
    'use strict';

    var ActionPermissionsReadonlyCell;
    var BaseView = require('oroui/js/app/views/base/view');
    var PermissionCollectionView = require('orouser/js/datagrid/cell/permission/permission-collection-view');
    var PermissionReadOnlyView = require('orouser/js/datagrid/cell/permission/permission-readonly-view');

    ActionPermissionsReadonlyCell = BaseView.extend({
        tagName: 'td',

        initialize: function(options) {
            var permissionsView = new PermissionCollectionView({
                collection: this.model.get('permissions'),
                itemView: PermissionReadOnlyView
            });
            this.subview('permissions', permissionsView);
            ActionPermissionsReadonlyCell.__super__.initialize.call(this, options);
        },

        render: function() {
            this.$el.append(this.subview('permissions').$el);
        }
    });

    return ActionPermissionsReadonlyCell;
});
