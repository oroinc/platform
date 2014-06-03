/*global define*/
define(['orodatagrid/js/datagrid/action/model-action', 'oroworkflow/js/activation-handler'],
function (ModelAction, ActivationHandler) {
    'use strict';

    /**
     * Activate AJAX action, triggers confirmation dialog and activate workflow definition
     *
     * @export  orodatagrid/js/datagrid/action/workflow-activate-action
     * @class   orodatagrid.datagrid.action.WorkflowActivateAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return ModelAction.extend({
        execute: function () {
            var datagrid = this.datagrid;

            this.on('activation_start', function() {
                datagrid.showLoading();
            });
            this.on('activation_success', function() {
                datagrid.hideLoading();
                datagrid.collection.fetch({reset: true});
            });
            this.on('activation_error', function(xhr) {
                datagrid.hideLoading();
            });

            ActivationHandler.call(this, this.getLink());
        }
    });
});
