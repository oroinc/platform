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
                __('Export started, please wait...')
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
            if (data.success) {
                if (data.readsCount > 0) {
                    message = __(
                        'Export performed successfully, {{ count }} entities were exported.',
                        {'count': data.readsCount}
                    );
                    var resultFileLink = '<a href="' + data.url + '" class="no-hash" target="_blank">' +
                        __('Download result file') + '</a>';
                    message += ' ' + resultFileLink;
                    messageType = 'success';
                } else {
                    message = __('No entities found for export.');
                    messageType = 'info';
                }
            } else {
                message = __(
                    'Export operation fails, {{ count }} error(s) found.',
                    {'count': data.errorsCount}
                );
                var errorLogLink = '<a href="' + data.url + '" class="no-hash" target="_blank">' +
                    __('Download error log') + '</a>';
                message += ' ' + errorLogLink;
                messageType = 'error';
            }
            messenger.notificationMessage(messageType, message);
        },

        handleDataTemplateDownloadErrorMessage: function() {
            return messenger.notificationMessage(
                'error',
                __('Errors occured while downloading the template.')
            );
        }
    };
});
