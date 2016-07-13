define(function(require) {
    'use strict';

    var ActionPermissionsCell;
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var PermissionCollectionView = require('orouser/js/datagrid/cell/permission/permission-collection-view');

    ActionPermissionsCell = BaseView.extend({
        tagName: 'td',

        initialize: function(options) {
            var permissionsView = new PermissionCollectionView({
                collection: this.model.get('permissions')
            });
            this.subview('permissions', permissionsView);
            this.listenTo(this.model.get('permissions'), 'change', this.onAccessLevelChange);
            ActionPermissionsCell.__super__.initialize.call(this, options);
        },

        render: function() {
            this.$el.append(this.subview('permissions').$el);
        },

        /**
         * Handles access level change of some permission item
         *
         * @param {PermissionModel} model
         */
        onAccessLevelChange: function(model) {
            mediator.trigger('securityAccessLevelsComponent:link:click', {
                accessLevel: model.get('access_level'),
                identityId: model.get('identity'),
                permissionName: model.get('name'),
                group: this.model.get('group'),
                category: this.model.get('group'),
                isInitialValue: !model.isAccessLevelChanged()
            });
        }
    });

    return ActionPermissionsCell;
});
