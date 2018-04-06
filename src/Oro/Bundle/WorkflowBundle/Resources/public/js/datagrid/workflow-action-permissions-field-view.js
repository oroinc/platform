define(function(require) {
    'use strict';

    var WorkflowActionPermissionsFieldView;
    var mediator = require('oroui/js/mediator');
    var ActionPermissionsFieldView = require('orouser/js/datagrid/action-permissions-field-view');

    WorkflowActionPermissionsFieldView = ActionPermissionsFieldView.extend({
        /**
         * @inheritDoc
         */
        constructor: function WorkflowActionPermissionsFieldView() {
            WorkflowActionPermissionsFieldView.__super__.constructor.apply(this, arguments);
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
