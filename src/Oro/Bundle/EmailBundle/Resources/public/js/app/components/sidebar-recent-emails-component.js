define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var module = require('module');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    var EmailNotificationCountModel =
        require('oroemail/js/app/models/email-notification/email-notification-count-model');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        listen: {
            'change:settings model': 'updateCollectionRouteParams'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var count = 0;
            var config = module.config();
            var settings = this.model.get('settings');
            if ('unreadEmailsCount' in config) {
                count = _.find(config.unreadEmailsCount, function(item) {
                    return Number(item.id) === Number(settings.folderId);
                });
                count = count !== void 0 ? count.num : 0;
            }
            this.model.emailNotificationCountModel = new EmailNotificationCountModel({unreadEmailsCount: count});
            this.listenTo(this.model.emailNotificationCountModel, 'change:unreadEmailsCount', this.updateCount);
            this.updateCount();

            this.model.emailNotificationCollection = new EmailNotificationCollection([]);
            this.model.emailNotificationCollection.setRouteParams(settings);
        },

        updateCount: function() {
            var itemsCounter = Number(this.model.emailNotificationCountModel.get('unreadEmailsCount'));
            this.model.set({
                itemsCounter: itemsCounter,
                highlighted: Boolean(itemsCounter)
            });
        },

        updateCollectionRouteParams: function(model, settings) {
            model.emailNotificationCollection.setRouteParams(settings);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.model.emailNotificationCollection.dispose();
            delete this.model;
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
