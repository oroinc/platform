define(['oro/widget-manager'],
function(widgetManager) {
    'use strict';

    /**
     * Step form to transition buttons handler
     *
     * @export  oro/workflow-step-form-handler
     * @class   oro.WorkflowStepFormHandler
     */
    return function(stepFormWidgetAlias, transitionButtonsWidgetAlias) {
        widgetManager.getWidgetInstanceByAlias(stepFormWidgetAlias, function(widget) {
            var lastFocused = null;
            var followFocus = function() {
                widget.$('form :input').on('focus', function() {
                    lastFocused = this.id;
                });
                if (lastFocused) {
                    var field = widget.$('#' + lastFocused);
                    field.focus();
                    field.select();
                }
            };
            followFocus();
            widget.on('widgetRender', followFocus);

            widget.on('adoptedFormSubmit', function() {
                widgetManager.getWidgetInstanceByAlias(transitionButtonsWidgetAlias, function(transitionsWidget){
                    transitionsWidget.$el.find('button').prop("disabled", true);
                });
            });

            widget.on('formSave', function() {
                widgetManager.getWidgetInstanceByAlias(transitionButtonsWidgetAlias, function(transitionsWidget){
                    transitionsWidget.loadContent();
                });
            });
        });
    }
});
