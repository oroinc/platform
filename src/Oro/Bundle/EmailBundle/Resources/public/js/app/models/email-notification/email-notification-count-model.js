define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-notification-count-model
     */
    const EmailNotificationCountModel = BaseModel.extend({
        defaults: {
            unreadEmailsCount: 0
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailNotificationCountModel(...args) {
            EmailNotificationCountModel.__super__.constructor.apply(this, args);
        }
    });

    return EmailNotificationCountModel;
});
