define(function(require) {
    'use strict';

    var EmailNotificationModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-notification-model
     */
    EmailNotificationModel = BaseModel.extend({
        replyRoute: '',

        replyAllRoute: '',

        forwardRoute: '',

        id: '',

        seen: '',

        subject: '',

        bodyContent: '',

        fromName: '',

        linkFromName: '',

        /**
         * @inheritDoc
         */
        constructor: function EmailNotificationModel() {
            EmailNotificationModel.__super__.constructor.apply(this, arguments);
        }
    });

    return EmailNotificationModel;
});
