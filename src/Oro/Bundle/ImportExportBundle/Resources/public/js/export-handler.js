define(['underscore', 'orotranslation/js/translator', 'oroui/js/messenger'
    ], function(_, __, messenger) {
    'use strict';

    /**
     * @export oroimportexport/js/export-handler
     * @name   oro.exportHandler
     */
    return {
        /**
         * Shows 'Export started' notification and returns and object represents this message
         *
         * @returns {Object}
         */
        startExportNotificationMessage: function() {
            return messenger.notificationMessage(
                'success',
                __('oro.importexport.export.starting.message')
            );
        },

        /**
         * Handles export 'success' response
         *
         * @param {Object} data
         */
        handleExportResponse: function(data) {
            var message;
            var messageType;
            if (data.hasOwnProperty('success') && data.success) {
                message = __('oro.importexport.export.success.message');
                messageType = 'success';
            } else {
                message = __('oro.importexport.export.fail.message');
                messageType = 'error';
            }
            messenger.notificationMessage(messageType, message);
        },

        handleDataTemplateDownloadErrorMessage: function() {
            return messenger.notificationMessage(
                'error',
                __('oro.importexport.export.error_template_download.message')
            );
        }
    };
});
