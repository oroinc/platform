define(function(require) {
    'use strict';

    var NewEmailMessageComponent;
    var _ = require('underscore');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var module = require('module');
    var sync = require('orosync/js/sync');
    var BaseComponent = require('oroui/js/app/components/base/component');

    NewEmailMessageComponent = BaseComponent.extend({
        notificationElement: null,
        debouncedHandler: null,
        messageTpl: '<%=_.__("oro.email.notification.new_email")%>' +
            '<span class="separator">|</span><a href="<%=url %>"><%=_.__("Read") %></a>',
        initialize: function(options) {
            var channel = module.config().clankEvent;
            this.notificationElement = options.notificationElement;
            this.debouncedHandler = _.debounce(_.bind(this.onNewEmailReceived, this), 6000, true);
            sync.subscribe(channel, _.bind(this.onMessage, this));
        },

        onMessage: function(response) {
            var message = JSON.parse(response);
            if (_.result(message, 'hasNewEmail') === true) {
                this.debouncedHandler();
            }
        },

        onNewEmailReceived: function() {
            var message;
            if (this.notificationElement === null) {
                message = _.template(this.messageTpl)({url: routing.generate('oro_email_user_emails')});
                messenger.notificationFlashMessage('info', message);
            } else {
                if (this.notificationElement.parent().hasClass('open') === false) {
                    this.notificationElement.show().delay(5000).fadeOut(1000);
                }
            }
        }
    });

    return NewEmailMessageComponent;
});
