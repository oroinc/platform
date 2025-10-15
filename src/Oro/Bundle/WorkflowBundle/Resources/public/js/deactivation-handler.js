import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import Modal from 'oroui/js/modal';
import Messenger from 'oroui/js/messenger';

/**
 * Deactivation handler
 *
 * @export  oroworkflow/js/deactivation-handler
 * @class   oroworkflow.WorkflowDeactivationHandler
 */
export default function(url, label, hideNotifications) {
    const el = this;
    const confirmDeactivation = new Modal({
        title: __('oro.workflow.workflowdefinition.deactivate', {label: label}),
        content: __('oro.workflow.workflowdefinition.reset_workflow_data_message'),
        okText: __('oro.workflow.workflowdefinition.deactivate_button_text')
    });

    confirmDeactivation.on('ok', function() {
        el.trigger('deactivation_start');
        $.ajax({
            url: url,
            type: 'POST',
            success: function(response) {
                if (response.message && !hideNotifications) {
                    Messenger.notificationFlashMessage('success', response.message);
                }
                el.trigger('deactivation_success', [response]);
            },
            error: function(xhr, textStatus, error) {
                el.trigger('deactivation_error', [xhr, textStatus, error]);
            }
        });
    });

    confirmDeactivation.open();
};
