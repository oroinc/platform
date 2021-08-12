define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const EmailNotificationCountView = BaseView.extend({
        autoRender: true,

        listen: {
            'change:unreadEmailsCount model': 'render'
        },

        template: require('tpl-loader!oroemail/templates/email-notification/email-notification-icon-view.html'),

        /**
         * @inheritdoc
         */
        constructor: function EmailNotificationCountView(options) {
            EmailNotificationCountView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = EmailNotificationCountView.__super__.getTemplateData.call(this);

            if (data.unreadEmailsCount === void 0) {
                data.unreadEmailsCount = 0;
            }

            return data;
        }
    });

    return EmailNotificationCountView;
});
