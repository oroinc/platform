define(function(require) {
    'use strict';

    var EmailNotificationComponent;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var module = require('module');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var sync = require('orosync/js/sync');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DesktopEmailNotificationView =
        require('oroemail/js/app/views/email-notification/email-notification-collection-view');
    var MobileEmailNotificationView =
        require('oroemail/js/app/views/email-notification/mobile-email-notification-view');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');
    var EmailNotificationCountView =
        require('oroemail/js/app/views/email-notification/email-notification-count-view');

    EmailNotificationComponent = BaseComponent.extend({
        view: null,
        collection: null,
        countModel: null,

        initialize: function(options) {
            if (!options || !(options.countModel instanceof Backbone.Model)) {
                throw new TypeError('Invalid "countModel" option of EmailNotificationComponent');
            }
            _.extend(this, _.pick(options, ['countModel']));
            this.initCollection(options);
            this.initViews(options);
            this.initSync();
        },

        initCollection: function(options) {
            if (this.collection) {
                this.usedOutOfScopeCollection = true;
                return;
            }
            var emails = options.emails || [];
            if (typeof emails === 'string') {
                emails = JSON.parse(emails);
            }
            this.collection = new EmailNotificationCollection(emails);
        },

        initViews: function(options) {
            var EmailNotificationView = tools.isMobile() ? MobileEmailNotificationView : DesktopEmailNotificationView;
            this.view = new EmailNotificationView({
                el: options._sourceElement,
                collection: this.collection,
                countNewEmail: this.countModel.get('unreadEmailsCount'),
                folderId: options.folderId,
                defaultActionId: options.defaultActionId,
                hasMarkAllButton: Boolean(options.hasMarkAllButton),
                hasMarkVisibleButton: Boolean(options.hasMarkVisibleButton)
            });
            if (options._iconElement && options._iconElement.length) {
                this.countView = new EmailNotificationCountView({
                    el: options._iconElement,
                    model: this.countModel
                });
            }
        },

        initSync: function() {
            var channel = module.config().clankEvent;
            var handlerNotification = _.bind(this.handlerNotification, this);
            sync.subscribe(channel, handlerNotification);
            this.once('dispose', function() {
                sync.unsubscribe(channel, handlerNotification);
            });
        },

        handlerNotification: function(response) {
            var self = this;
            response = JSON.parse(response);
            if (response) {
                var hasNewEmail = response.hasNewEmail;
                self.loadLastEmail(hasNewEmail);
            }
        },

        loadLastEmail: function(hasNewEmail) {
            this.collection.fetch({
                success: _.bind(function(collection) {
                    this.countModel.set('unreadEmailsCount', collection.unreadEmailsCount);
                    if (hasNewEmail) {
                        this.view.showNotification();
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                }, this)
            });
        },

        dispose: function() {
            if (this.disposed) {
                return true;
            }
            if (this.usedOutOfScopeCollection) {
                // prevent collection disposing
                delete this.collection;
            }
            EmailNotificationComponent.__super__.dispose.call(this);
        }
    });

    return EmailNotificationComponent;
});
