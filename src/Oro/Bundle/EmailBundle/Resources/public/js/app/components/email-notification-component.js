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
    'oroui/js/messenger'
], function($, __, _, routing, mediator, sync, BaseComponent, EmailNotificationView, messenger) {
    'use strict';

    var EmailNotification;

    EmailNotification = BaseComponent.extend({
        view: null,
        collection: null,

        initialize: function() {
            this.initView()
                .initSync()
                .render();
        },

        initView: function() {
            this.view = new EmailNotificationView({
                el: '.email-notification-menu'
            });

            return this;
        },

        render: function() {
            this.view.render();
        },

        initSync: function() {
            var clankEvent = this.view.getClankEvent();
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
                url: routing.generate('oro_api_get_email_last'),
                success: function(response) {
                    self.view.collection.reset(response.emails);
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
