define(function(require) {
    'use strict';

    var EmailNotificationCollection;
    var EmailNotificationModel = require('./email-notification-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailNotificationCollection = BaseCollection.extend({
        model: EmailNotificationModel,
        markAllAsRead: function() {
            for (var i in this.models) {
                this.models[i].set({'seen':1});
            }
        }
    });

    return EmailNotificationCollection;
});
