define([
    'jquery',
    'oroui/js/mediator',
    'routing',
    'oro/buttons-widget'
], function($, mediator, routing, ButtonsWidget) {
    'use strict';

    /**
     * Save and transit button click handler
     *
     * @export  oroworkflow/js/save-and-transit-handler
     * @class   oroworkflow.WorkflowSaveAndTransitHandler
     */
    return function() {
        const saveBtn = $(this);
        // Modify form to stay on edit page after submit
        const form = saveBtn.closest('form');
        const actionInput = form.find('input[name="input_action"]');
        actionInput.val('save_and_stay');
        const formId = form.prop('id');

        // On form submit response check for errors
        mediator.once('page:update', function() {
            const hasErrors = $('.alert-error, .validation-error').length > 0;
            if (!hasErrors) {
                const idRegexp = /update\/(\d+).*/;
                const responseForm = $('#' + formId);
                const elementIdMatch = idRegexp.exec(responseForm.prop('action'));
                if (elementIdMatch.length > 1) {
                    // In case when no errors occurred load transitions for created entity
                    const containerEl = $('<div class="hidden invisible"/>');
                    $('body').append(containerEl);
                    const transitionsWidget = new ButtonsWidget({
                        el: containerEl,
                        elementFirst: false,
                        url: routing.generate('oro_workflow_widget_buttons', {
                            entityId: elementIdMatch[1],
                            entityClass: saveBtn.data('entity-class')
                        })
                    });
                    transitionsWidget.on('renderComplete', function(el) {
                        // Try to execute required transition
                        const transition = el.find('#transition-' + saveBtn.data('workflow') +
                            '-' + saveBtn.data('transition'));
                        if (transition.length) {
                            transition.on('transitionHandlerInitialized', function() {
                                transition.data('executor').call();
                                containerEl.remove();
                            });
                        }
                    });
                    transitionsWidget.render();
                }
            }
        });
        form.submit();
    };
});
