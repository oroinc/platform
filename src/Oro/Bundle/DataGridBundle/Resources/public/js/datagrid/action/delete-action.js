/* global define */
define(['underscore', 'oro/messenger', 'oro/translator', 'oro/delete-confirmation', 'oro/modal', 'oro/datagrid/model-action'],
    function(_, messenger, __, DeleteConfirmation, Modal, ModelAction) {
        'use strict';

        /**
         * Delete action with confirm dialog, triggers REST DELETE request
         *
         * @export  oro/datagrid/delete-action
         * @class   oro.datagrid.DeleteAction
         * @extends oro.datagrid.ModelAction
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
            execute: function() {
                this.getConfirmDialog(_.bind(this.doDelete, this)).open();
            },

            /**
             * Confirm delete item
             */
            doDelete: function() {
                var self = this;
                this.model.destroy({
                    url: this.getLink(),
                    wait: true,
                    error: function() {
                        self.getErrorDialog().open();
                    },
                    success: function() {
                        var messageText = __('Item deleted');
                        messenger.notificationFlashMessage('success', messageText);
                    }
                });
            },

            /**
             * Get view for error modal
             *
             * @return {oro.Modal}
             */
            getErrorDialog: function() {
                if (!this.errorModal) {
                    this.errorModal = new Modal({
                        title: __('Delete Error'),
                        content: __('Cannot delete item.'),
                        cancelText: false
                    });
                }
                return this.errorModal;
            }
        });
    });
