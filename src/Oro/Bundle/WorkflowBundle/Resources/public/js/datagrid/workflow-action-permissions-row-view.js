define(function(require) {
    'use strict';

    var WorkflowActionPermissionsRowView;
    var _ = require('underscore');
    var ActionPermissionsRowView = require('orouser/js/datagrid/action-permissions-row-view');
    var FieldView = require('./workflow-action-permissions-field-view');

    WorkflowActionPermissionsRowView = ActionPermissionsRowView.extend({
        fieldItemView: FieldView,
        onAccessLevelChange: function(model) {
            // override to prevent triggering of 'securityAccessLevelsComponent:link:click' event
        }
    });

    return WorkflowActionPermissionsRowView;
});
