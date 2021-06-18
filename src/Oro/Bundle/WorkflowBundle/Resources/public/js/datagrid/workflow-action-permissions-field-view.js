define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const ActionPermissionsFieldView = require('orouser/js/datagrid/action-permissions-field-view');

    const WorkflowActionPermissionsFieldView = ActionPermissionsFieldView.extend({
        /**
         * @inheritdoc
         */
        constructor: function WorkflowActionPermissionsFieldView(options) {
            WorkflowActionPermissionsFieldView.__super__.constructor.call(this, options);
        },

        onAccessLevelChange: function(model) {
            // override to prevent marking 'Entity' permissions grid tabs as changed
            mediator.trigger('securityAccessLevelsComponent:link:click', {
                accessLevel: model.get('access_level'),
                identityId: model.get('identity'),
                permissionName: model.get('name')
            });
        }
    });

    return WorkflowActionPermissionsFieldView;
});
