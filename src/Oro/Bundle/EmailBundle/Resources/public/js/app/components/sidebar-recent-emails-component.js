define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var module = require('module');
    var mediator = require('oroui/js/mediator');
    var sync = require('orosync/js/sync');
    var unreadEmailsCount = _.result(module.config(), 'unreadEmailsCount') || [];
    var channel = module.config().clankEvent;
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    var EmailNotificationCountModel =
        require('oroemail/js/app/models/email-notification/email-notification-count-model');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        debouncedNotificationHandler: null,
        listen: {
            'change:settings model': 'onSettingsChange'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function() {
            var settings = this.model.get('settings');
            var count = _.find(unreadEmailsCount, function(item) {
                return Number(item.id) === Number(settings.folderId);
            });
            count = count !== void 0 ? count.num : 0;
            this.debouncedNotificationHandler = _.debounce(_.bind(this._notificationHandler, this), 3000, true);
            this.model.emailNotificationCountModel = new EmailNotificationCountModel({unreadEmailsCount: count});
            this.listenTo(this.model.emailNotificationCountModel, 'change:unreadEmailsCount', this.updateSidebarCount);
            this.updateSidebarCount();

            this.model.emailNotificationCollection = new EmailNotificationCollection([], {
                routeParameters: _.pick(settings, ['limit', 'folderId'])
            });

            this.listenTo(this.model.emailNotificationCollection, 'sync', this.updateModelFromCollection);
            this.model.emailNotificationCollection.fetch();

            sync.subscribe(channel, this.debouncedNotificationHandler);
        },

        updateSidebarCount: function() {
            var itemsCounter = Number(this.model.emailNotificationCountModel.get('unreadEmailsCount'));
            this.model.set({
                itemsCounter: itemsCounter,
                highlighted: Boolean(itemsCounter)
            });
        },

        updateModelFromCollection: function(collection) {
            var id = Number(_.result(this.model.get('settings'), 'folderId') || 0);
            _.each(unreadEmailsCount, function(item) {
                if (item.id === id) {
                    item.num = collection.unreadEmailsCount;
                }
            });
            this.model.emailNotificationCountModel.set('unreadEmailsCount', collection.unreadEmailsCount);
        },

        onSettingsChange: function(model, settings) {
            _.delay(function() {
                model.emailNotificationCollection.setRouteParams(settings);
            }, 0);
        },

        _notificationHandler: function() {
            this.model.emailNotificationCollection.fetch();
            mediator.trigger('datagrid:doRefresh:user-email-grid');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            sync.unsubscribe(channel, this.debouncedNotificationHandler);
            this.model.emailNotificationCollection.dispose();
            delete this.model;
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
