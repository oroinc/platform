define(function(require) {
    'use strict';

    var ActionPermissionsFieldView;
    var mediator = require('oroui/js/mediator');
    var ActionPermissionsReadonlyFieldView = require('./action-permissions-readonly-field-view');
    var PermissionView = require('orouser/js/datagrid/permission/permission-view');
    var AccessLevelsCollection = require('orouser/js/models/role/access-levels-collection');

    ActionPermissionsFieldView = ActionPermissionsReadonlyFieldView.extend({
        autoRender: false,
        animationDuration: 0,
        className: 'field-permission-container clearfix',
        template: require('tpl!orouser/templates/datagrid/action-permissions-field-view.html'),
        permissionView: PermissionView,
        accessLevelRouteName: null,

        initialize: function(options) {
            ActionPermissionsFieldView.__super__.initialize.call(this, options);
            var permissionCollection = this.model.get('permissions');
            var routeParameters = {
                routeName: ActionPermissionsFieldView.accessLevelRouteName || 'oro_security_access_levels'
            };
            permissionCollection.each(function(model) {
                routeParameters.oid = model.get('identity').replace(/\\/g, '_');
                routeParameters.permission = model.get('name');

                model.accessLevels = new AccessLevelsCollection([], {
                    routeParameters: routeParameters
                });
            });
            this.listenTo(permissionCollection, 'change', this.onAccessLevelChange);
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

    return ActionPermissionsFieldView;
});
