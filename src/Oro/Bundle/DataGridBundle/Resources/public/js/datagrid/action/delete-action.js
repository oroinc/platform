define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/delete-confirmation',
    './model-action'
], function(_, messenger, __, DeleteConfirmation, ModelAction) {
    'use strict';

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  oro/datagrid/action/delete-action
     * @class   oro.datagrid.action.DeleteAction
     * @extends oro.datagrid.action.ModelAction
     */
    const DeleteAction = ModelAction.extend({

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
         * @inheritdoc
         */
        constructor: function DeleteAction(options) {
            DeleteAction.__super__.constructor.call(this, options);
        },

        /**
         * Execute delete model
         */
        execute: function() {
            this.getConfirmDialog(() => {
                this.doDelete(this.messages);
            }).open();
        },

        /**
         * Confirm delete item
         */
        doDelete: function(messages) {
            this.model.destroy({
                url: this.getLink(),
                wait: true,
                success: function() {
                    messenger.notificationFlashMessage('success', __(messages.success));
                }
            });
        }
    });

    return DeleteAction;
});
