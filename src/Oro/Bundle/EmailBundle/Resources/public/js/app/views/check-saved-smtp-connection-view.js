define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const BaseView = require('oroui/js/app/views/base/view');

    const CheckSavedConnectionView = BaseView.extend({
        route: 'oro_email_check_saved_smtp_connection',

        events: {
            'click [data-role=check-saved-smtp-connection]': 'checkSmtpConnection'
        },

        /**
         * @inheritdoc
         */
        constructor: function CheckSavedConnectionView(options) {
            CheckSavedConnectionView.__super__.constructor.call(this, options);
        },

        checkSmtpConnection: function(event) {
            const $messageContainer = this.$el.find('.check-smtp-connection-messages');
            mediator.execute('showLoading');

            $.ajax({
                type: 'POST',
                url: this.getUrl(),
                success: response => {
                    if (response) {
                        this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                    } else {
                        this.showMessage('success', 'oro.email.smtp_connection.success', $messageContainer);
                    }
                },
                errorHandlerMessage: false,
                error: () => {
                    this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                },
                complete: () => {
                    mediator.execute('hideLoading');
                }
            });

            return false;
        },

        showMessage: function(type, message, container) {
            messenger.notificationFlashMessage(type, __(message), {
                container: container,
                delay: 5000
            });
        },

        getUrl: function() {
            const $settingsForm = this.$el.closest('form');

            return routing.generate(this.route, {
                scopeClass: $settingsForm.data('scope-class'),
                scopeId: $settingsForm.data('scope-id')
            });
        }
    });

    return CheckSavedConnectionView;
});
