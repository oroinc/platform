/*global define*/
define(['oro/datagrid/action/ajax-action', 'orotranslation/js/translator', 'oroui/js/messenger'
    ], function (AjaxAction, __, messenger) {
    'use strict';

    /**
     * Schedule channel sync action, triggers AJAX request
     *
     * @export  oro/datagrid/action/ajax-action
     * @class   oro.datagrid.action.AjaxAction
     * @extends oro.datagrid.action.ModelAction
     */
    return AjaxAction.extend({
        _onAjaxSuccess: function (data) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
            }
            this._showAjaxSuccessMessage(data);
        },

        _showAjaxSuccessMessage: function (data) {
            var defaultMessage = data.successful ? this.messages.success : this.messages.error,
                message = data.message || __(defaultMessage);

            if (message) {
                messenger.notificationFlashMessage(data.successful ? 'success' : 'error', message);
            }
        }
    });
});
