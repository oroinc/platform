define(function(require) {
    'use strict';

    var EmailNotification;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var module = require('module');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var tools = require('oroui/js/tools');
    var sync = require('orosync/js/sync');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DesktopEmailNotificationView =
        require('oroemail/js/app/views/email-notification/email-notification-collection-view');
    var MobileEmailNotificationView =
        require('oroemail/js/app/views/email-notification/mobile-email-notification-view');
    var EmailNotificationCollection =
        require('oroemail/js/app/models/email-notification/email-notification-collection');

    EmailNotification = BaseComponent.extend({
        view: null,
        collection: null,

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.initCollection()
                .initView()
                .initSync();
        },

        initCollection: function() {
            var emails = this.options.emails || [];
            if (typeof emails === 'string') {
                emails = JSON.parse(emails);
            }
            this.collection = new EmailNotificationCollection(emails);

            return this;
        },

        initView: function() {
            var EmailNotificationView = tools.isMobile() ? MobileEmailNotificationView : DesktopEmailNotificationView;

            this.view = new EmailNotificationView({
                el: this.options._sourceElement,
                collection: this.collection,
                countNewEmail: this.options.count
            });
            this.view.render();

            return this;
        },

        initSync: function() {
            var channel = module.config().clankEvent;
            var handlerNotification = _.bind(this.handlerNotification, this);
            sync.subscribe(channel, handlerNotification);
            this.once('dispose', function() {
                sync.unsubscribe(channel, handlerNotification);
            });
            return this;
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
            var self = this;
            $.ajax({
                url: routing.generate('oro_email_last'),
                success: function(response) {
                    self.collection.reset(response.emails);
                    self.view.setCount(response.count);
                    if (hasNewEmail) {
                        self.view.showNotification();
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                },
                error: function(model, response) {
                    messenger.showErrorMessage(__('oro.email.error.get_email_last'), response.responseJSON || {});
                }
            });
        }
    });

    return EmailNotification;
});
