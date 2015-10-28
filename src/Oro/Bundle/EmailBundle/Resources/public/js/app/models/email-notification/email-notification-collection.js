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
            this.unreadEmailsCount = 0;
        },

        parse: function(response, q) {
            this.checkServerResponse(response, q);
            if (this.disposed) {
                return;
            }

            this.unreadEmailsCount = response.count;
            // format response to regular backbone one
            response = {
                data: response.emails
            };

            return EmailNotificationCollection.__super__.parse.call(this, response, q);
        },

        //TODO: remove after server side gets work correctly
        checkServerResponse: function(response) {
            var length = response.emails.length;
            var count = Number(response.count);
            var limit = this._route.get('limit');
            if (length > limit || length < Math.min(limit, count)) {
                throw new Error('Wrong server response', response);
            } else {
                response.emails.forEach(function(element, index) {
                    if (element.seen && index < count || !element.seen && index >= count) {
                        window.console.error('Wrong server response', response);
                    }
                });
            }
        }
    });

    return EmailNotificationCollection;
});
