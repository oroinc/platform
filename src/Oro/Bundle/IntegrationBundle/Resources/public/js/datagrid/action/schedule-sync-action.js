/*global define*/
define(['orodatagrid/js/datagrid/action/ajax-action', 'orotranslation/js/translator', 'oronavigation/js/navigation',
    'oroui/js/messenger'],
    function (AjaxAction, __, Navigation, messenger) {
    'use strict';

    /**
     * Schedule channel sync action, triggers AJAX request
     *
     * @export  orodatagrid/js/datagrid/action/ajax-action
     * @class   orodatagrid.datagrid.action.AjaxAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return AjaxAction.extend({
        _onAjaxSuccess: function (data, textStatus, jqXHR) {
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
            
            var navigation = Navigation.getInstance();
            if (navigation) {
                navigation.processClicks($('.job-view-link'));
            }

        }
    });
});
