define(function(require) {
    'use strict';

    const EmailNotificationModel = require('./email-notification-model');
    const RoutingCollection = require('oroui/js/app/models/base/routing-collection');
    const error = require('oroui/js/error');

    /**
     * @export oroemail/js/app/models/email-notification-collection
     */
    const EmailNotificationCollection = RoutingCollection.extend({
        model: EmailNotificationModel,

        routeDefaults: {
            routeName: 'oro_email_last',
            routeQueryParameterNames: ['limit', 'folderId'],
            limit: 10,
            folderId: 0
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailNotificationCollection(...args) {
            EmailNotificationCollection.__super__.constructor.apply(this, args);
        },

        setRouteParams: function(params) {
            this._route.set({
                limit: params.limit,
                folderId: params.folderId
            });
        },

        markAllAsRead: function() {
            for (const i in this.models) {
                if (this.models.hasOwnProperty(i)) {
                    this.models[i].set({seen: 1});
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

        // TODO: remove after server side gets work correctly
        checkServerResponse: function(response) {
            const length = response.emails.length;
            const count = Number(response.count);
            const limit = this._route.get('limit');
            if (length > limit || length < Math.min(limit, count)) {
                throw new Error('Wrong server response', response);
            } else {
                response.emails.forEach(function(element, index) {
                    if (element.seen && index < count || !element.seen && index >= count) {
                        error.showErrorInConsole(response);
                    }
                });
            }
        }
    });

    return EmailNotificationCollection;
});
