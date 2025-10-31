import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import messenger from 'oroui/js/messenger';

/**
 * @export oroimportexport/js/export-handler
 * @name   oro.exportHandler
 */
export default {
    /**
     * Handles export 'success' response
     *
     * @param {Object} data
     */
    handleExportResponse: function(data) {
        let message;
        let messageType;
        if (data.hasOwnProperty('success') && data.success) {
            message = __('oro.importexport.export.success.message');
            messageType = 'success';
        } else {
            message = __('oro.importexport.export.fail.message');
            messageType = 'error';
        }
        messenger.notificationMessage(messageType, message);

        if (data.messages) {
            _.each(data.messages, function(messages, type) {
                _.each(messages, function(message) {
                    messenger.notificationMessage(type, message);
                });
            });
        }
    },

    handleDataTemplateDownloadErrorMessage: function() {
        return messenger.notificationMessage(
            'error',
            __('oro.importexport.export.error_template_download.message')
        );
    }
};
