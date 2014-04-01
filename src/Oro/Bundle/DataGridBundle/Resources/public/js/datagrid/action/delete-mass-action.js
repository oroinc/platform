/*global define*/
define(['underscore', 'orotranslation/js/translator', 'oroui/js/delete-confirmation', './mass-action'
    ], function (_, __, DeleteConfirmation, MassAction) {
    'use strict';

    /**
     * Delete mass action class.
     *
     * @export  orodatagrid/js/datagrid/action/delete-mass-action
     * @class   orodatagrid.datagrid.action.DeleteMassAction
     * @extends orodatagrid.datagrid.action.MassAction
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
