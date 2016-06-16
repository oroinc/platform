define([
    'oro/datagrid/action/model-action',
    'oroworkflow/js/activation-handler',
    'oroui/js/messenger'
], function(ModelAction, ActivationHandler, messenger) {
    'use strict';

    var WorkflowActivateAction;

    /**
     * Activate AJAX action, triggers confirmation dialog and activate workflow definition
     *
     * @export  oro/datagrid/action/workflow-activate-action
     * @class   oro.datagrid.action.WorkflowActivateAction
     * @extends oro.datagrid.action.ModelAction
     */
    WorkflowActivateAction = ModelAction.extend({
        execute: function() {
            var datagrid = this.datagrid;

            this.on('activation_success', function(response) {
                messenger.notificationFlashMessage('success', response.message);

                if (response.deactivatedMessage) {
                    messenger.notificationFlashMessage('info', response.deactivatedMessage);
                }

                datagrid.hideLoading();
                datagrid.collection.fetch({reset: true});
            });

            ActivationHandler.call(this, this.getLink());
        }
    });

    return WorkflowActivateAction;
});
