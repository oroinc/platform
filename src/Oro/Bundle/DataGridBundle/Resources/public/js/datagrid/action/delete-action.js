define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/delete-confirmation',
    './model-action'
], function(_, messenger, __, DeleteConfirmation, ModelAction) {
    'use strict';

    var DeleteAction;

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  oro/datagrid/action/delete-action
     * @class   oro.datagrid.action.DeleteAction
     * @extends oro.datagrid.action.ModelAction
     */
    DeleteAction = ModelAction.extend({

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'Are you sure you want to delete this item?',
            confirm_ok: 'Yes, Delete',
            confirm_cancel: 'Cancel',
            success: 'Item deleted'
        },

        /**
         * Execute delete model
         */
        execute: function() {
            this.getConfirmDialog(_.bind(this.doDelete, this)).open();
        },

        /**
         * Confirm delete item
         */
        doDelete: function() {
            this.model.destroy({
                url: this.getLink(),
                wait: true,
                error: function(req, resp) {
                    var messageText;
                    if (resp) {
                        var errorCode = 'responseJSON' in resp ? resp.responseJSON.code : resp.status;
                        switch (errorCode) {
                            case 403:
                                messageText = __('You do not have permission to perform this action.');
                                break;
                            case 500:
                                if (resp.responseJSON.message) {
                                    messageText = __(resp.responseJSON.message);
                                } else {
                                    messageText = __('oro.ui.unexpected_error');
                                }
                                break;
                            default:
                                messageText = __('oro.ui.unexpected_error');
                        }
                    }
                    messenger.notificationFlashMessage('error', messageText);
                },
                success: function() {
                    var messageText = __('Item deleted');
                    messenger.notificationFlashMessage('success', messageText);
                }
            });
        }
    });

    return DeleteAction;
});
