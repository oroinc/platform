/*global define*/
define(['oro/datagrid/model-action', 'oro/delete-confirmation', 'oro/translator'
    ], function (ModelAction, DeleteConfirmation, __) {
    'use strict';

    /**
     * Ajax delete action, triggers REST AJAX request
     *
     * @export  oro/datagrid/ajaxdelete-action
     * @class   oro.datagrid.AjaxDeleteAction
     * @extends oro.datagrid.ModelAction
     */
    return ModelAction.extend({
        confirmation: true,

        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        defaultMessages: {
            confirm_title: __('Delete confirmation'),
            confirm_content: __('Are you sure you want to remove this item?'),
            confirm_ok: __('Yes'),
            success: __('Removed.'),
            error: __('Not removed.'),
            empty_selection: __('Please, select item to remove.')
        }
    });
});
