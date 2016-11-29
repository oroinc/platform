define(function(require) {
    'use strict';

    var WorkflowActionPermissionsFieldView;
    var ActionPermissionsFieldView = require('orouser/js/datagrid/action-permissions-field-view');

    WorkflowActionPermissionsFieldView = ActionPermissionsFieldView.extend({
        onAccessLevelChange: function(model) {
            // override to prevent triggering of 'securityAccessLevelsComponent:link:click' event
        }
    });

    return WorkflowActionPermissionsFieldView;
});
