define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const config = require('module-config').default(module.id);
    const mediator = require('oroui/js/mediator');
    const sync = require('orosync/js/sync');
    const channel = config.wsChannel;
    const countCache = require('oroemail/js/util/unread-email-count-cache');
    const EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    const EmailNotificationCountModel =
        require('oroemail/js/app/models/email-notification/email-notification-count-model');

    const SidebarRecentEmailsComponent = BaseComponent.extend({
        /**
         * @type {Function}
         */
        notificationHandler: null,

        listen: {
            'change:settings model': 'onSettingsChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function SidebarRecentEmailsComponent(options) {
            SidebarRecentEmailsComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            const settings = this.model.get('settings');
            const count = countCache.get(settings.folderId);
            this.model.emailNotificationCountModel = new EmailNotificationCountModel({unreadEmailsCount: count});
            this.listenTo(this.model.emailNotificationCountModel, 'change:unreadEmailsCount', this.updateSidebarCount);
            this.updateSidebarCount();

            this.model.emailNotificationCollection = new EmailNotificationCollection([], {
                routeParameters: _.pick(settings, ['limit', 'folderId'])
            });
            this.listenTo(this.model.emailNotificationCollection, 'request', this.onFetchCollection);
            this.listenTo(this.model.emailNotificationCollection, 'sync', this.updateModelFromCollection);
            this.model.emailNotificationCollection.fetch();

            this.notificationHandler = _.debounce(this._notificationHandler.bind(this), 1000);
            sync.subscribe(channel, this.notificationHandler);
        },

        updateSidebarCount: function() {
            const itemsCounter = Number(this.model.emailNotificationCountModel.get('unreadEmailsCount'));
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
            const id = Number(_.result(this.model.get('settings'), 'folderId') || 0);
            countCache.set(id, collection.unreadEmailsCount);
            this.model.trigger('end-loading');
            this.model.emailNotificationCountModel.set('unreadEmailsCount', collection.unreadEmailsCount);
        },

        onSettingsChange: function(model, settings) {
            _.delay(function() {
                model.emailNotificationCollection.setRouteParams(settings);
            }, 0);
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
