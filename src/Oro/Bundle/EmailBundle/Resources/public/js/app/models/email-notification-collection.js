define(function(require) {
    'use strict';

    var EmailNotificationCollection;
    var EmailNotificationModel = require('./email-notification-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailNotificationCollection = BaseCollection.extend({
        model: EmailNotificationModel
    });

    return EmailNotificationCollection;
});
