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
                'info',
                __('oro.importexport.export.started.message')
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
            if (! data.success) {
                message = __(
                    'oro.importexport.export.fail.message',
                    {'count': data.errorsCount}
                );
                var errorLogLink = '<a href="' + data.url + '" class="no-hash" target="_blank">' +
                    __('oro.importexport.export.download_error_log.message') + '</a>';
                message += ' ' + errorLogLink;
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
