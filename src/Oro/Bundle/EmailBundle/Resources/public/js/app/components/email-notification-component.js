/*jslint nomen: true*/
/*global define*/
define([
    'jquery',
    'orotranslation/js/translator',
    'routing',
    'oroui/js/mediator',
    'orosync/js/sync',
    'oroui/js/app/components/base/component',
    'oroemail/js/app/views/email-notification-view',
    'oroemail/js/app/models/email-notification-collection'
], function (jquery, __, routing, mediator, sync, BaseComponent, EmailNotificationView, EmailNotificationCollection) {
    'use strict';

    var EmailNotification;

    EmailNotification = BaseComponent.extend({
        view: null,
        collection: null,

        initialize: function (options) {
            //debugger;
            this.initView()
                .initSync()
                .initData()
                .render();
        },

        initView: function () {
            this.view = new EmailNotificationView({
                el: '.email-notification-menu'
            });

            return this;
        },

        initData: function() {
            var emails = this.view.getEmails();
            this.collection = new EmailNotificationCollection(emails);
            this.view.setCollection(this.collection);
            this.view.initEvents();

            return this;
        },

        render: function() {
            // todo: to fix double execution
            this.view.render()
        },

        initSync: function(options) {
            var clankEvent = this.view.getClankEvent();
            var f = this.onNewEmail.bind(this);
            sync.subscribe(clankEvent, f);

            return this;
        },

        onNewEmail:function(r) {
            var self = this;
            r = JSON.parse(r);
            if (r) {

                    $.ajax({
                        url: routing.generate('oro_api_api_emails_notification_info'),
                        success: function(r) {
                            self.view.collection.add(r);
                        }
                    })
            }
        }
    });

    return EmailNotification;
});
