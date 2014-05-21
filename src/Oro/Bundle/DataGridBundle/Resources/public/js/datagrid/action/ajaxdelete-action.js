/*global define*/
define(['./model-action', 'oroui/js/delete-confirmation'
    ], function (ModelAction, DeleteConfirmation) {
    'use strict';

    /**
     * Ajax delete action, triggers REST AJAX request
     *
     * @export  orodatagrid/js/datagrid/action/ajaxdelete-action
     * @class   orodatagrid.datagrid.action.AjaxdeleteAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return ModelAction.extend({
        confirmation: true,

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'Are you sure you want to remove this item?',
            confirm_ok: 'Yes',
            success: 'Removed.',
            error: 'Not removed.',
            empty_selection: 'Please, select item to remove.'
        }
    });
});
