import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import routing from 'routing';
import mediator from 'oroui/js/mediator';
import messenger from 'oroui/js/messenger';
import BaseView from 'oroui/js/app/views/base/view';

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
        const $settingsForm = this.$el.closest('form');
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
    }
});

export default CheckSavedConnectionView;
