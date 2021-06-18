define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-notification-model
     */
    const EmailNotificationModel = BaseModel.extend({
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
         * @inheritdoc
         */
        constructor: function EmailNotificationModel(...args) {
            EmailNotificationModel.__super__.constructor.apply(this, args);
        }
    });

    return EmailNotificationModel;
});
