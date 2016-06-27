define([
    'oro/datagrid/action/model-action',
    'oroworkflow/js/deactivation-handler'
], function(ModelAction, DeactivationHandler) {
    'use strict';

    var WorkflowDeactivateAction;

    /**
     * Activate AJAX action, triggers confirmation dialog and deactivate workflow definition
     *
     * @export  oro/datagrid/action/workflow-deactivate-action
     * @class   oro.datagrid.action.WorkflowDeactivateAction
     * @extends oro.datagrid.action.ModelAction
     */
    WorkflowDeactivateAction = ModelAction.extend({
        execute: function() {
            var datagrid = this.datagrid;

            this.on('deactivation_start', function() {
                datagrid.showLoading();
            });
            this.on('deactivation_success', function() {
                datagrid.hideLoading();
                datagrid.collection.fetch({reset: true});
            });
            this.on('deactivation_error', function(xhr) {
                datagrid.hideLoading();
            });

            DeactivationHandler.call(this, this.getLink(), this.model.get('label'));
        }
    });

    return WorkflowDeactivateAction;
});
