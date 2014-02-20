/*global define*/
define(['./model-action', 'oro/delete-confirmation', 'oro/translator'
    ], function (ModelAction, DeleteConfirmation, __) {
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
            confirm_title: __('Delete Confirmation'),
            confirm_content: __('Are you sure you want to remove this item?'),
            confirm_ok: __('Yes'),
            success: __('Removed.'),
            error: __('Not removed.'),
            empty_selection: __('Please, select item to remove.')
        }
    });
});
