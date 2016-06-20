define(['orotranslation/js/translator', 'routing', 'oro/dialog-widget'],
function(__, routing, DialogWidget) {
    'use strict';

    /**
     * Activation handler
     *
     * @export oroworkflow/js/activation-handler
     * @class  oroworkflow.WorkflowActivationHandler
     */
    return function(url) {
        var el = this;
        var formWidget = new DialogWidget({
            title: __('oro.workflow.workflowdefinition.activate', {label : this.model.get('label')}),
            url: routing.generate('oro_workflow_definition_activate_from_widget', {name: this.model.get('name')}),
            stateEnabled: false,
            incrementalPosition: false,
            loadingMaskEnabled: true,
            dialogOptions: {
                modal: true,
                resizable: true,
                width: 500,
                autoResize: true
            }
        });

        formWidget.on('formSave', function(response) {
            el.trigger('activation_success', response);
        });

        formWidget.render();
    };
});
