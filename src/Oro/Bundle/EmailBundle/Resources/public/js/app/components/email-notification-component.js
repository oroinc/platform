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
], function (jquery, __, routing, mediator, sync, BaseComponent, EmailNotificationView) {
    'use strict';

    var EmailNotification;

    EmailNotification = BaseComponent.extend({
        view: null,

        initialize: function (options) {
            this.initView()
                .initSync();
        },

        initView: function () {
            this.view = new EmailNotificationView({
                el: '.email-notification-menu'
            });

            return this;
        },

        initSync: function(options) {
            var clankEvent = this.view.getClankEvent();
            sync.subscribe(clankEvent, this.onNewEmail);

            return this;
        },

        onNewEmail:function(e) {
            alert(1);
            console.log(e);
        }
    });

    return EmailNotification;
});
