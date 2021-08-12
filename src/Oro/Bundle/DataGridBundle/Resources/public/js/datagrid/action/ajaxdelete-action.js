define([
    './model-action',
    'oroui/js/delete-confirmation',
    'oroui/js/mediator'
], function(ModelAction, DeleteConfirmation, mediator) {
    'use strict';

    /**
     * Ajax delete action, triggers REST AJAX request
     *
     * @export  oro/datagrid/action/ajaxdelete-action
     * @class   oro.datagrid.action.AjaxdeleteAction
     * @extends oro.datagrid.action.ModelAction
     */
    const AjaxdeleteAction = ModelAction.extend({
        confirmation: true,

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /** @property {String} */
        requestType: 'DELETE',

        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'Are you sure you want to delete this item?',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel',
            success: 'Removed.',
            error: 'Not removed.',
            empty_selection: 'Please, select item to remove.'
        },

        /**
         * @inheritdoc
         */
        constructor: function AjaxdeleteAction(options) {
            AjaxdeleteAction.__super__.constructor.call(this, options);
        },

        _doAjaxRequest: function() {
            mediator.trigger('datagrid:beforeRemoveRow:' + this.datagrid.name, this.model);

            AjaxdeleteAction.__super__._doAjaxRequest.call(this);
        },

        _onAjaxSuccess: function(...args) {
            mediator.trigger('datagrid:afterRemoveRow:' + this.datagrid.name);

            AjaxdeleteAction.__super__._onAjaxSuccess.apply(this, args);
        }
    });

    return AjaxdeleteAction;
});
