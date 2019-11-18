define(function(require) {
    'use strict';

    var CheckSavedConnectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var BaseView = require('oroui/js/app/views/base/view');

    CheckSavedConnectionView = BaseView.extend({
        route: 'oro_email_check_saved_smtp_connection',

        events: {
            'click [data-role=check-saved-smtp-connection]': 'checkSmtpConnection'
        },

        /**
         * @inheritDoc
         */
        constructor: function CheckSavedConnectionView(options) {
            CheckSavedConnectionView.__super__.constructor.call(this, options);
        },

        checkSmtpConnection: function(event) {
            var $messageContainer = this.$el.find('.check-smtp-connection-messages');
            var $settingsForm = this.$el.closest('form');
            mediator.execute('showLoading');

            $.ajax({
                type: 'GET',
                url: routing.generate(
                    this.route,
                    {
                        scopeClass: $settingsForm.data('scope-class'),
                        scopeId: $settingsForm.data('scope-id')
                    }
                ),
                success: _.bind(function(response) {
                    if (response) {
                        this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                    } else {
                        this.showMessage('success', 'oro.email.smtp_connection.success', $messageContainer);
                    }
                }, this),
                errorHandlerMessage: false,
                error: _.bind(function() {
                    this.showMessage('error', 'oro.email.smtp_connection.error', $messageContainer);
                }, this),
                complete: function() {
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
        }
    });

    return CheckSavedConnectionView;
});
