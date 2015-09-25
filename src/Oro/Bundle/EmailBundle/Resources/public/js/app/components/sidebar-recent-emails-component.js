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
            var count;
            var config = module.config();
            var settings = this.model.get('settings');
            if ('unreadEmailsCount' in config) {
                count = _.find(config.unreadEmailsCount, function(item) {
                    return Number(item.id) === Number(settings.folderId);
                });
                if (count !== void 0) {
                    this.model.set({itemsCounter: count.num});
                }
            }
            this.model.emailNotificationCountModel = new EmailNotificationCountModel({
                unreadEmailsCount: options.count
            });
            this.listenTo(this.model.emailNotificationCountModel, 'change:unreadEmailsCount', this.updateCount);

            this.model.emailNotificationCollection = new EmailNotificationCollection([]);
            this.model.emailNotificationCollection.setRouteParams(settings);
        },

        updateCount: function() {
            this.model.set({
                itemsCounter: this.model.emailNotificationCountModel.get('unreadEmailsCount')
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
