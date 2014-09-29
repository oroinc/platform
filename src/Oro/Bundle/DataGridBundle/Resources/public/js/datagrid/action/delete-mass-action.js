/*global define*/
define([
    'oroui/js/delete-confirmation',
    './mass-action'
], function (DeleteConfirmation, MassAction) {
    'use strict';

    var DeleteMassAction;

    /**
     * Delete mass action class.
     *
     * @export  oro/datagrid/action/delete-mass-action
     * @class   oro.datagrid.action.DeleteMassAction
     * @extends oro.datagrid.action.MassAction
     */
    DeleteMassAction = MassAction.extend({
        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /** @property {Object} */
        defaultMessages: {
            confirm_title: 'Delete Confirmation',
            confirm_content: 'Are you sure you want to do remove selected items?',
            confirm_ok: 'Yes, Delete',
            confirm_cancel: 'Cancel',
            success: 'Selected items were removed.',
            error: 'Selected items were not removed.',
            empty_selection: 'Please, select items to remove.'
        }
    });

    return DeleteMassAction;
});
