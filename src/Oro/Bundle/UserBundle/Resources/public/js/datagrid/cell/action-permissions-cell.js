define(function(require) {
    'use strict';

    var ActionPermissionsCell;
    var BaseView = require('oroui/js/app/views/base/view');
    var PermissionCollectionView = require('orouser/js/datagrid/cell/permission/permission-collection-view');

    ActionPermissionsCell = BaseView.extend({
        tagName: 'td',

        initialize: function(options) {
            var permissions = new PermissionCollectionView({
                collection: this.model.get('permissions')
            });
            this.subview('permissions', permissions);
            ActionPermissionsCell.__super__.initialize.call(this, options);
        },

        render: function() {
            this.$el.append(this.subview('permissions').$el);
        }
    });

    return ActionPermissionsCell;
});
