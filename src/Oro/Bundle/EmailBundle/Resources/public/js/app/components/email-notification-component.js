/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'orotranslation/js/translator',
    'underscore',
    'routing',
    'oroui/js/mediator',
    'orosync/js/sync',
    'oroui/js/app/components/base/component',
    'oroemail/js/app/views/email-notification-view',
    'oroemail/js/app/models/email-notification-collection',
    'oroui/js/messenger'
], function($,
            __,
            _,
            routing,
            mediator,
            sync,
            BaseComponent,
            EmailNotificationView,
            EmailNotificationCollection,
            messenger) {
    'use strict';

    var EmailNotification;

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
            this.collection = new EmailNotificationCollection(JSON.parse(this.options.emails));

            return this;
        },

        initView: function() {
            this.view = new EmailNotificationView({
                el: this.options._sourceElement,
                collection: this.collection,
                count: this.options.count
            });

            return this;
        },

        initSync: function() {
            var clankEvent = this.options.clank_event;
            var handlerNotification = _.bind(this.handlerNotification, this);
            sync.subscribe(clankEvent, handlerNotification);

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
                    self.view.collection.reset();
                    self.view.collection.add(response.emails);
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
