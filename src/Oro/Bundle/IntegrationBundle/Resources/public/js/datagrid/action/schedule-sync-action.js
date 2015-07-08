define([
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oro/datagrid/action/ajax-action'
], function(__, messenger, AjaxAction) {
    'use strict';

    var ScheduleSyncAction;

    /**
     * Schedule channel sync action, triggers AJAX request
     *
     * @export  oro/datagrid/action/schedule-sync-action
     * @class   oro.datagrid.action.ScheduleSyncAction
     * @extends oro.datagrid.action.AjaxAction
     */
    ScheduleSyncAction = AjaxAction.extend({
        _onAjaxSuccess: function(data) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
            }
            this._showAjaxSuccessMessage(data);
        },

        _showAjaxSuccessMessage: function(data) {
            var defaultMessage = data.successful ? this.messages.success : this.messages.error;
            var message = data.message || __(defaultMessage);

            if (message) {
                messenger.notificationFlashMessage(data.successful ? 'success' : 'error', message);
            }
        }
    });

    return ScheduleSyncAction;
});
