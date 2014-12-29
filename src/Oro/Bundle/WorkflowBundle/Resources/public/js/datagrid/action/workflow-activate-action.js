/*global define*/
define([
    'oro/datagrid/action/model-action',
    'oroworkflow/js/activation-handler'
], function (ModelAction, ActivationHandler) {
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
        execute: function () {
            var datagrid = this.datagrid;

            this.on('activation_start', function () {
                datagrid.showLoading();
            });
            this.on('activation_success', function () {
                datagrid.hideLoading();
                datagrid.collection.fetch({reset: true});
            });
            this.on('activation_error', function (xhr) {
                datagrid.hideLoading();
            });

            ActivationHandler.call(this, this.getLink());
        }
    });

    return WorkflowActivateAction;
});
