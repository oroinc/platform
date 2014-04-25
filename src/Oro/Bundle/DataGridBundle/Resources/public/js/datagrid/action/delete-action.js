/*global define*/
define(['underscore', 'oroui/js/messenger', 'orotranslation/js/translator', 'oroui/js/delete-confirmation', './model-action'
    ], function (_, messenger, __, DeleteConfirmation, ModelAction) {
    'use strict';

    /**
     * Delete action with confirm dialog, triggers REST DELETE request
     *
     * @export  orodatagrid/js/datagrid/action/delete-action
     * @class   orodatagrid.datagrid.action.DeleteAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return ModelAction.extend({

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'Are you sure you want to delete this item?',
            confirm_ok: 'Yes, Delete',
            success: 'Item deleted'
        },

        /**
         * Execute delete model
         */
        execute: function () {
            this.getConfirmDialog(_.bind(this.doDelete, this)).open();
        },

        /**
         * Confirm delete item
         */
        doDelete: function () {
            var that = this;
            this.model.destroy({
                url: this.getLink(),
                wait: true,
                error: function () {
                    var messageText = __('Cannot delete item.');
                    messenger.notificationFlashMessage('error', messageText);
                },
                success: function () {
                    var messageText = __(that.messages.success);
                    messenger.notificationFlashMessage('success', messageText);
                }
            });
        }
    });
});
