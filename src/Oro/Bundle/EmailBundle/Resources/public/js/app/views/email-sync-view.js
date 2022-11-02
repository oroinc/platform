define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const $ = require('jquery');
    const messenger = require('oroui/js/messenger');

    const EmailSyncView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'syncPath', 'processingMessage', 'errorHandlerMessage', 'actionProcessing', 'actionSync'
        ]),

        events: {
            'click [data-role="sync"]': 'onSync'
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailSyncView(options) {
            EmailSyncView.__super__.constructor.call(this, options);
        },

        onSync: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            if ($button.attr('disabled')) {
                return;
            }

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: this.syncPath,
                errorHandlerMessage: this.errorHandlerMessage,
                beforeSend: function() {
                    $button.html(this.actionProcessing);
                    $button.attr('disabled', 'disabled');
                }.bind(this),
                success: function(response) {
                    if (response.error !== undefined) {
                        messenger.notificationFlashMessage('error', response.error);
                    } else {
                        messenger.notificationFlashMessage('info', this.processingMessage);
                    }
                }.bind(this),
                complete: function() {
                    $button.html(this.actionSync);
                    $button.removeAttr('disabled');
                }.bind(this)
            });
        }
    });

    return EmailSyncView;
});
