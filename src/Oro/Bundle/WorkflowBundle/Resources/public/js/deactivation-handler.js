define(['jquery', 'orotranslation/js/translator', 'oroui/js/modal', 'oroui/js/messenger', 'oroui/js/error'],
function($, __, Modal, Messenger, Error) {
    'use strict';

    /**
     * Deactivation handler
     *
     * @export  oroworkflow/js/deactivation-handler
     * @class   oroworkflow.WorkflowDeactivationHandler
     */
    return function(data, url, hideNotifications) {
        var el = this;
        var confirmDeactivation = new Modal({
            title:   __('oro.workflow.workflowdefinition.deactivate', {label : data.label}),
            content: __('Attention: This action will reset all workflow data for this entity.'),
            okText:  __('Yes, Deactivate')
        });

        confirmDeactivation.on('ok', function() {
            el.trigger('deactivation_start');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.message && !hideNotifications) {
                        Messenger.notificationFlashMessage('success', response.message);
                    }
                    el.trigger('deactivation_success', [response]);
                },
                error: function(xhr, textStatus, error) {
                    Error.handle({}, xhr, {enforce: true});
                    el.trigger('deactivation_error', [xhr, textStatus, error]);
                }
            });
        });

        confirmDeactivation.open();
    };
});
