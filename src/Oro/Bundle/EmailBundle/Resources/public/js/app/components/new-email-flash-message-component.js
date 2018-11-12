define(function(require) {
    'use strict';

    var NewEmailFlashMessageComponent;
    var _ = require('underscore');
    var routing = require('routing');
    var messenger = require('oroui/js/messenger');
    var NewEmailMessageComponent = require('oroemail/js/app/components/new-email-message-component');

    NewEmailFlashMessageComponent = NewEmailMessageComponent.extend({
        messageTpl: _.template('<%=_.__("oro.email.notification.new_email")%>' +
            '<span class="separator">|</span><a href="<%=url %>"><%=_.__("Read") %></a>'),

        /**
         * @inheritDoc
         */
        constructor: function NewEmailFlashMessageComponent() {
            NewEmailFlashMessageComponent.__super__.constructor.apply(this, arguments);
        },

        onNewEmail: function() {
            var message = this.messageTpl({url: routing.generate('oro_email_user_emails')});
            messenger.notificationFlashMessage('info', message);
        }
    });

    return NewEmailFlashMessageComponent;
});
