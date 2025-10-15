import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import Modal from 'oroui/js/modal';
import mediator from 'oroui/js/mediator';

/**
 * Reset button click handler
 *
 * @export  oroworkflow/js/delete-handler
 * @class   oroworkflow.WorkflowDeleteHandler
 */
export default function() {
    const element = $(this);
    if (element.data('_in-progress')) {
        return;
    }

    element.data('_in-progress', true);
    function resetInProgress() {
        element.data('_in-progress', false);
    }

    const confirmReset = new Modal({
        title: __('oro.workflow.workflowdefinition.reset'),
        content: __('oro.workflow.workflowdefinition.reset_message'),
        okText: __('oro.workflow.workflowdefinition.reset_button_text')
    });

    confirmReset.on('ok', function() {
        $.ajax({
            url: element.data('url'),
            type: 'DELETE',
            success: function() {
                mediator.execute('refreshPage');
            },
            errorHandlerMessage: __('Cannot reset workflow item data.'),
            error: function() {
                resetInProgress();
            }
        });
    });

    confirmReset.on('cancel', function() {
        resetInProgress();
    });

    confirmReset.open();
};
