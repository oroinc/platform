import mediator from 'oroui/js/mediator';
import ActionPermissionsRowView from 'orouser/js/datagrid/action-permissions-row-view';
import FieldView from './workflow-action-permissions-field-view';

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

export default WorkflowActionPermissionsRowView;
