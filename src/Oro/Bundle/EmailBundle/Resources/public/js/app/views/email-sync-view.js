define(function(require) {
    'use strict';

    var EmailSyncView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var messenger = require('oroui/js/messenger');

    EmailSyncView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat([
            'syncPath', 'processingMessage', 'errorHandlerMessage', 'actionProcessing', 'actionSync'
        ]),

        events: {
            'click [data-role="sync"]': 'onSync'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailSyncView() {
            EmailSyncView.__super__.constructor.apply(this, arguments);
        },

        onSync: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            if ($button.attr('disabled')) {
                return;
            }

            $.ajax({
                type: 'GET',
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
