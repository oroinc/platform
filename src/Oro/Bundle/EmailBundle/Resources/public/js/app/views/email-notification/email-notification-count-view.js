define(function(require) {
    'use strict';

    var EmailNotificationCountView;
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationCountView = BaseView.extend({
        autoRender: true,
        template: require('tpl!oroemail/templates/email-notification/email-notification-icon-view.html'),
        initialize: function() {
            EmailNotificationCountView.__super__.initialize.apply(this, arguments);
            this.listenTo(this.model, 'change:unreadEmailsCount', this.render);
        }
    });

    return EmailNotificationCountView;
});
