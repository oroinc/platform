define(function(require) {
    'use strict';

    var EmailNotification;
    var _ = require('underscore');
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

    EmailNotification = BaseComponent.extend({
        view: null,
        collection: null,

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.initCollection();
            this.initViews();
            this.initSync();
        },

        initCollection: function() {
            if (this.options.collection) {
                this.collection = this.options.collection;
                this.usedOutOfScopeCollection = true;
                return;
            }
            var emails = this.options.emails || [];
            if (typeof emails === 'string') {
                emails = JSON.parse(emails);
            }
            this.collection = new EmailNotificationCollection(emails);
        },

        initViews: function() {
            var EmailNotificationView = tools.isMobile() ? MobileEmailNotificationView : DesktopEmailNotificationView;

            this.view = new EmailNotificationView({
                el: this.options._sourceElement,
                collection: this.collection,
                countNewEmail: this.options.count
            });
            if (this.options._iconElement) {
                this.countView = new EmailNotificationCountView({
                    el: this.options._iconElement,
                    model: this.options.countModel
                });
            }
            this.view.render();
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
                    this.countModel.set('unreadEmailCount', collection.unreadEmailsCount);
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
            EmailNotification.__super__.dispose.call(this);
        }
    });

    return EmailNotification;
});
