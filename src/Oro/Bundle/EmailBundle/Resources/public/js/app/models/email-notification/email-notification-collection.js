define(function(require) {
    'use strict';

    var EmailNotificationCollection;
    var EmailNotificationModel = require('./email-notification-model');
    var RoutingCollection = require('oroui/js/app/models/base/routing-collection');

    /**
     * @export oroemail/js/app/models/email-notification-collection
     */
    EmailNotificationCollection = RoutingCollection.extend({
        model: EmailNotificationModel,
        routeDefaults: {
            routeName: 'oro_email_last',
            routeQueryParameterNames: ['limit', 'folderId'],
            limit: 10,
            folderId: 0
        },

        setRouteParams: function(params) {
            this._route.set({
                limit: params.limit,
                folderId: params.folderId
            });
        },

        markAllAsRead: function() {
            for (var i in this.models) {
                if (this.models.hasOwnProperty(i)) {
                    this.models[i].set({'seen': 1});
                }
            }
        },

        parse: function(response, q) {
            if (this.disposed) {
                return;
            }

            this.unreadEmailsCount = response.count;
            // format response to regular backbone one
            response = {
                data: response.emails
            };

            return EmailNotificationCollection.__super__.parse.call(this, response, q);
        }
    });

    return EmailNotificationCollection;
});
