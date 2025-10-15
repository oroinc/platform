import ModelAction from 'oro/datagrid/action/model-action';
import ActivationHandler from 'oroworkflow/js/activation-handler';
import messenger from 'oroui/js/messenger';

/**
 * Activate AJAX action, triggers confirmation dialog and activate workflow definition
 *
 * @export  oro/datagrid/action/workflow-activate-action
 * @class   oro.datagrid.action.WorkflowActivateAction
 * @extends oro.datagrid.action.ModelAction
 */
const WorkflowActivateAction = ModelAction.extend({
    execute: function() {
        const datagrid = this.datagrid;

        this.on('activation_success', function(response) {
            messenger.notificationFlashMessage('success', response.message);

            if (response.deactivatedMessage) {
                messenger.notificationFlashMessage('info', response.deactivatedMessage);
            }

            datagrid.hideLoading();
            datagrid.collection.fetch({reset: true});
        });

        ActivationHandler.call(this, this.getLink(), this.model.get('name'), this.model.get('label'));
    }
});

export default WorkflowActivateAction;
