define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal',
    'oronavigation/js/navigation',
    'oroui/js/messenger',
    'oroui/js/delete-confirmation'
],
function($, _, __, Modal, Navigation, Messenger, DeleteConfirmation) {
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

        var confirmReset = new DeleteConfirmation({
            title:   __('Workflow reset'),
            content: element.data('message'),
            okText:  __('Yes, Reset')
        });

        confirmReset.on('ok', function() {
            $.ajax({
                url:  element.data('url'),
                type: 'DELETE',
                success: function(responce) {
                    var doReload = function() {
                        if (navigation) {
                            navigation.loadPage(true);
                        } else {
                            window.location.reload();
                        }
                    };

                    element.one('reset_success', doReload);
                    element.trigger('reset_success', [responce]);
                },
                error: function(xhr, textStatus, error) {
                    element.one('reset_failure', function() {
                        Messenger.notificationFlashMessage('error', __('Cannot reset workflow item data.'));
                    });
                    element.trigger('reset_failure', [xhr, textStatus, error]);
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
