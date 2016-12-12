define(function(require) {
    'use strict';

    var ActionPermissionsFieldView;
    var mediator = require('oroui/js/mediator');
    var ActionPermissionsReadonlyFieldView = require('./action-permissions-readonly-field-view');
    var PermissionView = require('orouser/js/datagrid/permission/permission-view');

    ActionPermissionsFieldView = ActionPermissionsReadonlyFieldView.extend({
        autoRender: false,
        animationDuration: 0,
        className: 'field-permission-container clearfix',
        template: require('tpl!orouser/templates/datagrid/action-permissions-field-view.html'),
        permissionView: PermissionView,

        initialize: function(options) {
            ActionPermissionsFieldView.__super__.initialize.call(this, options);
            this.listenTo(this.model.get('permissions'), 'change', this.onAccessLevelChange);
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
