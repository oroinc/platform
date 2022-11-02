define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const ActionPermissionsReadonlyFieldView = require('./action-permissions-readonly-field-view');
    const PermissionView = require('orouser/js/datagrid/permission/permission-view');

    const ActionPermissionsFieldView = ActionPermissionsReadonlyFieldView.extend({
        autoRender: false,

        animationDuration: 0,

        className: 'field-permission-container',

        template: require('tpl-loader!orouser/templates/datagrid/action-permissions-field-view.html'),

        permissionView: PermissionView,

        /**
         * @inheritdoc
         */
        constructor: function ActionPermissionsFieldView(options) {
            ActionPermissionsFieldView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
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
