define(function(require) {
    'use strict';

    var EmailNotificationCollection;
    var EmailNotificationModel = require('./email-notification-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export oroemail/js/app/models/email-notification-collection
     */
    EmailNotificationCollection = BaseCollection.extend({
        model: EmailNotificationModel,
        markAllAsRead: function() {
            for (var i in this.models) {
                if (this.models.hasOwnProperty(i)) {
                    this.models[i].set({'seen': 1});
                }
            }
        }
    });

    return EmailNotificationCollection;
});
