/* global define */
define(['underscore', 'oro/translator', 'oro/delete-confirmation', 'oro/datagrid/mass-action'],
function(_, __, DeleteConfirmation, MassAction) {
    'use strict';

    /**
     * Delete mass action class.
     *
     * @export  oro/datagrid/delete-mass-action
     * @class   oro.datagrid.DeleteMassAction
     * @extends oro.datagrid.MassAction
     */
    return MassAction.extend({
        /** @property {Function} */
        confirmModalConstructor: DeleteConfirmation,

        /** @property {Object} */
        defaultMessages: {
            confirm_title: __('Delete Confirmation'),
            confirm_content: __('Are you sure you want to do remove selected items?'),
            confirm_ok: __('Yes, Delete'),
            success: __('Selected items were removed.'),
            error: __('Selected items were not removed.'),
            empty_selection: __('Please, select items to remove.')
        }
    });
});
