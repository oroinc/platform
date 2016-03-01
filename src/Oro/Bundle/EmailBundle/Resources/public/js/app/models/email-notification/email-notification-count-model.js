define(function(require) {
    'use strict';

    var EmailNotificationCountModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-notification-count-model
     */
    EmailNotificationCountModel = BaseModel.extend({
        defaults: {
            unreadEmailsCount: 0
        }
    });

    return EmailNotificationCountModel;
});
