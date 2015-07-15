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

    var EmailNotification
        //options = {
        //    successMessage: 'oro.email.menu.mark_unread.success.message',
        //    errorMessage: 'oro.email.menu.mark_unread.error.message',
        //    redirect: '/'
        //}
        ;

    EmailNotification = BaseComponent.extend({
        view: null,

        initialize: function (options) {
            console.log(options);
            this.initView(options);
            this.initSync();
        },

        initView: function (options) {
            this.view = new EmailNotificationView({
                el: options._sourceElement
            });
        },

        initSync: function(options) {
            sync.subscribe('oro/email/user_36', this.onNewEmail);
        },

        onNewEmail:function(e) {
            alert(1);
            console.log(e);
        }
    });

    return EmailNotification;
});
