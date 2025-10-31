import __ from 'orotranslation/js/translator';
import messenger from 'oroui/js/messenger';
import AjaxAction from 'oro/datagrid/action/ajax-action';

/**
 * Schedule channel sync action, triggers AJAX request
 *
 * @export  oro/datagrid/action/schedule-sync-action
 * @class   oro.datagrid.action.ScheduleSyncAction
 * @extends oro.datagrid.action.AjaxAction
 */
const ScheduleSyncAction = AjaxAction.extend({
    /**
     * @inheritdoc
     */
    constructor: function ScheduleSyncAction(options) {
        ScheduleSyncAction.__super__.constructor.call(this, options);
    },

    _onAjaxSuccess: function(data) {
        if (this.reloadData) {
            this.datagrid.hideLoading();
        }
        this._showAjaxSuccessMessage(data);
    },

    _showAjaxSuccessMessage: function(data) {
        const defaultMessage = data.successful ? this.messages.success : this.messages.error;
        const message = data.message || __(defaultMessage);

        if (message) {
            messenger.notificationFlashMessage(data.successful ? 'success' : 'error', message);
        }
    }
});

export default ScheduleSyncAction;
