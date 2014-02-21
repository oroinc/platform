/*global define*/
define(['underscore', 'oro/messenger', 'orotranslation/js/translator', 'oro/delete-confirmation', './model-action'
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
            confirm_title: __('Delete Confirmation'),
            confirm_content: __('Are you sure you want to delete this item?'),
            confirm_ok: __('Yes, Delete')
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
            this.model.destroy({
                url: this.getLink(),
                wait: true,
                error: function () {
                    var messageText = __('Cannot delete item.');
                    messenger.notificationFlashMessage('error', messageText);
                },
                success: function () {
                    var messageText = __('Item deleted');
                    messenger.notificationFlashMessage('success', messageText);
                }
            });
        }
    });
});
