define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var module = require('module');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');

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
            var settings = options.model.get('settings');
            this.model = options.model;
            if ('unreadEmailsCount' in config) {
                count = _.find(config.unreadEmailsCount, function(item) {
                    return Number(item.id) === Number(settings.folderId);
                });
                if (count !== void 0) {
                    this.model.set({unreadEmailsCount: count.num});
                }
            }
            this.model.emailNotificationCollection = new EmailNotificationCollection([]);
            this.model.emailNotificationCollection.on('sync', this.onCollectionSync, this);
        },

        onCollectionSync: function() {
            this.model.set({
                unreadEmailsCount: this.model.emailNotificationCollection.unreadEmailsCount || ''
            });
        },

        updateCollectionRouteParams: function(model, settings) {
            model.emailNotificationCollection.setRouteParams(settings);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.model.emailNotificationCollection.off('sync', this.onCollectionSync, this);
            delete this.model;
            this.model.emailNotificationCollection.dispose();
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
