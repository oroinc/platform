define(function(require) {
    'use strict';

    var WorkflowActionPermissionsRowView;
    var mediator = require('oroui/js/mediator');
    var ActionPermissionsRowView = require('orouser/js/datagrid/action-permissions-row-view');
    var FieldView = require('./workflow-action-permissions-field-view');

    WorkflowActionPermissionsRowView = ActionPermissionsRowView.extend({
        fieldItemView: FieldView,

        /**
         * @inheritDoc
         */
        constructor: function WorkflowActionPermissionsRowView() {
            WorkflowActionPermissionsRowView.__super__.constructor.apply(this, arguments);
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
