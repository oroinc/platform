/*global define*/
define(['jquery', 'orotranslation/js/translator', 'oroui/js/modal', 'oroui/js/messenger', 'oroui/js/error'],
function($, __, Modal, Messenger, Error) {
    'use strict';

    /**
     * Activation handler
     *
     * @export  oroworkflow/js/activation-handler
     * @class   oroworkflow.WorkflowActivationHandler
     */
    return function(url) {
        var element = this;

        var confirmActivation = new Modal({
            title:   __('Workflow reset'),
            content: __('Attention: This action will reset all workflow data for this entity.'),
            okText:  __('Yes, Reset')
        });

        confirmActivation.on('ok', function() {
            element.trigger('activation_start');
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.message) {
                        Messenger.notificationFlashMessage('success', response.message);
                    }
                    element.trigger('activation_success', [response]);
                },
                error: function(xhr, textStatus, error) {
                    Error.handle({}, xhr, {enforce: true});
                    element.trigger('activation_error', [xhr, textStatus, error]);
                }
            })
        });

        confirmActivation.open();
    }
});
