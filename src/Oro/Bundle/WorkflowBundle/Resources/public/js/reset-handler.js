define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal',
    'oronavigation/js/navigation',
    'oroui/js/messenger'
],
function($, _, __, Modal, Navigation, Messenger) {
    'use strict';

    var navigation = Navigation.getInstance();
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
        var resetInProgress = function() {
            element.data('_in-progress', false);
        };

        var confirmReset = new Modal({
            title:   __('Workflow reset'),
            content: __('Attention: This action will reset workflow data for this record.'),
            okText:  __('Yes, Reset')
        });

        confirmReset.on('ok', function() {
            $.ajax({
                url:  element.data('url'),
                type: 'DELETE',
                success: function(responce) {
                    if (navigation) {
                        navigation.loadPage(true);
                    } else {
                        window.location.reload();
                    }
                },
                error: function() {
                    Messenger.notificationFlashMessage('error', __('Cannot reset workflow item data.'));
                    resetInProgress();
                }
            })
        });

        confirmReset.on('cancel', function() {
            resetInProgress();
        });

        confirmReset.open();
    }
});
