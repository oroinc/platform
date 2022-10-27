define(['underscore', 'orotranslation/js/translator', 'oroui/js/messenger'
], function(_, __, messenger) {
    'use strict';

    /**
     * @export oroimportexport/js/export-handler
     * @name   oro.exportHandler
     */
    return {
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
});
