define([
    'oro/datagrid/action/ajaxdelete-action',
    'oroui/js/delete-confirmation',
    'oroui/js/mediator'
], function(AjaxdeleteAction, DeleteConfirmation, mediator) {
    'use strict';

    var AjaxdeletefieldAction;

    /**
     * Ajax delete field action, shows confirmation dialogue, triggers REST AJAX request
     * and on success - refresh current page
     *
     * @export  oro/datagrid/action/ajaxdeletefield-action
     * @class   oro.datagrid.action.AjaxdeletefieldAction
     * @extends oro.datagrid.action.AjaxdeleteAction
     */
    AjaxdeletefieldAction = AjaxdeleteAction.extend({
        confirmation: true,

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'oro.entity_extend.delete_field.confirm_content',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel',
            success: 'Removed.',
            error: 'Not removed.',
            empty_selection: 'Please, select item to remove.'
        },

        /**
         * @inheritDoc
         */
        constructor: function AjaxdeletefieldAction() {
            AjaxdeletefieldAction.__super__.constructor.apply(this, arguments);
        },

        _onAjaxSuccess: function(data) {
            mediator.execute('refreshPage');
        }
    });

    return AjaxdeletefieldAction;
});

