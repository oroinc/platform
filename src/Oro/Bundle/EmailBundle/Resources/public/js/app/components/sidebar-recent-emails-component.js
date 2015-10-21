define(function(require) {
    'use strict';

    var SidebarRecentEmailsComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var module = require('module');
    var mediator = require('oroui/js/mediator');
    var sync = require('orosync/js/sync');
    var Backbone = require('backbone');
    var channel = module.config().clankEvent;
    var countCache = require('oroemail/js/util/unread-email-count-cache');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    var EmailNotificationCountModel =
        require('oroemail/js/app/models/email-notification/email-notification-count-model');

    SidebarRecentEmailsComponent = BaseComponent.extend({
        /**
         * @type {Function}
         */
        notificationHandler: null,
        listen: {
            'change:settings model': 'onSettingsChange',
            'widget_dialog:open mediator': 'onWidgetDialogOpen'
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var settings = this.model.get('settings');
            var count = countCache.get(settings.folderId);
            this.model.emailNotificationCountModel = new EmailNotificationCountModel({unreadEmailsCount: count});
            this.listenTo(this.model.emailNotificationCountModel, 'change:unreadEmailsCount', this.updateSidebarCount);
            this.updateSidebarCount();

            this.model.emailNotificationCollection = new EmailNotificationCollection([], {
                routeParameters: _.pick(settings, ['limit', 'folderId'])
            });
            this.listenTo(this.model.emailNotificationCollection, 'request', this.onFetchCollection);
            this.listenTo(this.model.emailNotificationCollection, 'sync', this.updateModelFromCollection);
            this.model.emailNotificationCollection.fetch();

            this.notificationHandler = _.debounce(_.bind(this._notificationHandler, this), 1000);
            sync.subscribe(channel, this.notificationHandler);
        },

        updateSidebarCount: function() {
            var itemsCounter = Number(this.model.emailNotificationCountModel.get('unreadEmailsCount'));
            this.model.set({
                itemsCounter: itemsCounter,
                highlighted: Boolean(itemsCounter)
            });
        },

        onFetchCollection: function() {
            if (!countCache.hasInitState()) {
                this.model.trigger('start-loading');
            }
        },

        updateModelFromCollection: function(collection) {
            var id = Number(_.result(this.model.get('settings'), 'folderId') || 0);
            countCache.set(id, collection.unreadEmailsCount);
            this.model.trigger('end-loading');
            this.model.emailNotificationCountModel.set('unreadEmailsCount', collection.unreadEmailsCount);
        },

        onSettingsChange: function(model, settings) {
            _.delay(function() {
                model.emailNotificationCollection.setRouteParams(settings);
            }, 0);
        },

        onWidgetDialogOpen: function() {
            Backbone.trigger('closeWidget', this.model.cid);
        },

        _notificationHandler: function() {
            countCache.clear();
            this.model.emailNotificationCollection.fetch();
            mediator.trigger('datagrid:doRefresh:user-email-grid');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            sync.unsubscribe(channel, this.notificationHandler);
            this.model.emailNotificationCollection.dispose();
            delete this.model;
            SidebarRecentEmailsComponent.__super__.dispose.call(this);
        }
    });

    return SidebarRecentEmailsComponent;
});
