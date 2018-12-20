define(function(require) {
    'use strict';

    var EmailNotificationCountView;
    var BaseView = require('oroui/js/app/views/base/view');

    EmailNotificationCountView = BaseView.extend({
        autoRender: true,

        listen: {
            'change:unreadEmailsCount model': 'render'
        },

        template: require('tpl!oroemail/templates/email-notification/email-notification-icon-view.html'),

        /**
         * @inheritDoc
         */
        constructor: function EmailNotificationCountView() {
            EmailNotificationCountView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = EmailNotificationCountView.__super__.getTemplateData.call(this);

            if (data.unreadEmailsCount === void 0) {
                data.unreadEmailsCount = 0;
            }

            return data;
        }
    });

    return EmailNotificationCountView;
});
