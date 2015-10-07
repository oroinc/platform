define(function(require) {
    'use strict';

    var EmailNotificationCountView;
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationCountView = BaseView.extend({
        autoRender: true,
        listen: {
            'change:unreadEmailsCount model': 'render'
        },
        template: require('tpl!oroemail/templates/email-notification/email-notification-icon-view.html')
    });

    return EmailNotificationCountView;
});
