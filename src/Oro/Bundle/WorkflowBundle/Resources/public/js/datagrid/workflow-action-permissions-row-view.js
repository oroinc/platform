define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const ActionPermissionsRowView = require('orouser/js/datagrid/action-permissions-row-view');
    const FieldView = require('./workflow-action-permissions-field-view');

    const WorkflowActionPermissionsRowView = ActionPermissionsRowView.extend({
        fieldItemView: FieldView,

        /**
         * @inheritdoc
         */
        constructor: function WorkflowActionPermissionsRowView(options) {
            WorkflowActionPermissionsRowView.__super__.constructor.call(this, options);
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

    return WorkflowActionPermissionsRowView;
});
