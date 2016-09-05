define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal',
    'oroui/js/mediator',
    'oroui/js/messenger'
], function($, _, __, Modal, mediator, Messenger) {
    'use strict';

    /**
     * Reset button click handler
     *
     * @export  oroworkflow/js/delete-handler
     * @class   oroworkflow.WorkflowDeleteHandler
     */
    return function() {
        var element = $(this);
        if (element.data('_in-progress')) {
            return;
        }

        element.data('_in-progress', true);
        function resetInProgress() {
            element.data('_in-progress', false);
        }

        var confirmReset = new Modal({
            title:   __('oro.workflow.workflowdefinition.reset'),
            content: __('oro.workflow.workflowdefinition.reset_message'),
            okText:  __('oro.workflow.workflowdefinition.reset_button_text')
        });

        confirmReset.on('ok', function() {
            $.ajax({
                url:  element.data('url'),
                type: 'DELETE',
                success: function() {
                    mediator.execute('refreshPage');
                },
                error: function() {
                    Messenger.notificationFlashMessage('error', __('Cannot reset workflow item data.'));
                    resetInProgress();
                }
            });
        });

        confirmReset.on('cancel', function() {
            resetInProgress();
        });

        confirmReset.open();
    };
});
