define(function(require) {
    'use strict';

    var ActionPermissionsRowView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var PermissionView = require('orouser/js/datagrid/permission/permission-view');
    var RolePermissionsActionView = require('orouser/js/datagrid/role-permissions-action-view');
    var ActionPermissionsReadonlyRowView = require('./action-permissions-readonly-row-view');
    var AccessLevelsCollection = require('orouser/js/models/role/access-levels-collection');
    var FieldView = require('orouser/js/datagrid/action-permissions-field-view');

    ActionPermissionsRowView = ActionPermissionsReadonlyRowView.extend({
        permissionItemView: PermissionView,
        fieldItemView: FieldView,
        initialize: function(options) {
            ActionPermissionsRowView.__super__.initialize.call(this, options);
            var fields = this.model.get('fields');
            if (fields.length) {
                var routeName = this.model.get('permissions').accessLevels.getRouteName();
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
            var rolePermissionsActionView = new RolePermissionsActionView({
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
