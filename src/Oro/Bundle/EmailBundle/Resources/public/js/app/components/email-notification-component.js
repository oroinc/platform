/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'orotranslation/js/translator',
    'routing',
    'oroui/js/mediator',
    'orosync/js/sync',
    'oroui/js/app/components/base/component',
    'oroemail/js/app/views/email-notification-view'
], function($, __, routing, mediator, sync, BaseComponent, EmailNotificationView) {
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
            var f = this.onNewEmail.bind(this);
            sync.subscribe(clankEvent, f);

            return this;
        },

        onNewEmail: function(response) {
            var self = this;
            response = JSON.parse(response);
            if (response) {
                var isNew = response.count_new;
                self.loadLastEmail(isNew);
            }
        },

        loadLastEmail: function(isNew) {
            var self = this;
            $.ajax({
                url: routing.generate('oro_api_get_email_last'),
                success: function(response) {
                    self.view.collection.reset(response.emails);
                    self.view.setCount(response.count);
                    if (isNew) {
                        self.view.showNotification();
                        mediator.trigger('datagrid:doRefresh:user-email-grid');
                    }
                }
            });
        }
    });

    return EmailNotification;
});
