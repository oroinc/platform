define(function(require) {
    'use strict';

    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const PermissionView = require('orouser/js/datagrid/permission/permission-view');
    const RolePermissionsActionView = require('orouser/js/datagrid/role-permissions-action-view');
    const ActionPermissionsReadonlyRowView = require('./action-permissions-readonly-row-view');
    const AccessLevelsCollection = require('orouser/js/models/role/access-levels-collection');
    const FieldView = require('orouser/js/datagrid/action-permissions-field-view');

    const ActionPermissionsRowView = ActionPermissionsReadonlyRowView.extend({
        permissionItemView: PermissionView,

        fieldItemView: FieldView,

        readonlyMode: false,

        /**
         * @inheritdoc
         */
        constructor: function ActionPermissionsRowView(options) {
            ActionPermissionsRowView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ActionPermissionsRowView.__super__.initialize.call(this, options);
            const fields = this.model.get('fields');
            if (fields.length) {
                const routeName = this.model.get('permissions').accessLevels.getRouteName();
                _.each(fields, function(field) {
                    field.permissions.each(function(model) {
                        model.accessLevels = new AccessLevelsCollection([], {
                            routeParameters: {
                                oid: model.get('identity').replace(/\\/g, '_'),
                                permission: model.get('name'),
                                routeName: routeName
                            }
                        });
                    });
                });
            }
            this.listenTo(this.model.get('permissions'), 'change', this.onAccessLevelChange);
        },

        render: function() {
            ActionPermissionsRowView.__super__.render.call(this);
            const rolePermissionsActionView = new RolePermissionsActionView({
                el: this.$('[data-name=row-action]'),
                accessLevels: this.model.get('permissions').accessLevels
            });
            this.subview('row-action', rolePermissionsActionView);
            this.listenTo(rolePermissionsActionView, 'row-access-level-change', function(data) {
                this.model.get('permissions').each(function(model) {
                    model.set(data);
                });
            });
            return this;
        },

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

    return ActionPermissionsRowView;
});
