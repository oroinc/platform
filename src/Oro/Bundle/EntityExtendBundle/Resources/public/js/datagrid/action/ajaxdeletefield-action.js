import AjaxdeleteAction from 'oro/datagrid/action/ajaxdelete-action';
import DeleteConfirmation from 'oroui/js/delete-confirmation';
import mediator from 'oroui/js/mediator';

/**
 * Ajax delete field action, shows confirmation dialogue, triggers REST AJAX request
 * and on success - refresh current page
 *
 * @export  oro/datagrid/action/ajaxdeletefield-action
 * @class   oro.datagrid.action.AjaxdeletefieldAction
 * @extends oro.datagrid.action.AjaxdeleteAction
 */
const AjaxdeletefieldAction = AjaxdeleteAction.extend({
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
     * @inheritdoc
     */
    constructor: function AjaxdeletefieldAction(options) {
        AjaxdeletefieldAction.__super__.constructor.call(this, options);
    },

    _onAjaxSuccess: function(data) {
        mediator.execute('refreshPage');
    }
});

export default AjaxdeletefieldAction;

