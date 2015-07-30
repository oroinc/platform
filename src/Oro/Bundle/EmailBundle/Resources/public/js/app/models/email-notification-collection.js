/*jslint nomen: true*/
/*global define*/
define([
        './email-notification-model',
        'oroui/js/app/models/base/collection'
], function(EmailNotificationModel, BaseCollection) {
    'use strict';

    var EmailNotificationCollection;

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
