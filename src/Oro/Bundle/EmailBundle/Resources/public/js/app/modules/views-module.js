define([
    'oroui/js/app/controllers/base/controller', 'module'
], function(BaseController, module) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery',
        'underscore',
        'orosync/js/sync',
        'oroui/js/messenger',
        'orotranslation/js/translator',
        'oroemail/js/app/components/email-notification-component',
        'oroemail/js/app/models/email-notification/email-notification-count-model'
    ], function($, _, sync, messenger, __, EmailNotificationComponent, EmailNotificationCountModel) {
        BaseController.addToReuse('emailNotification', {
            compose: function() {
                var $menu = $('.email-notification-menu');
                var channel = module.config().clankEvent;
                var onNewEmailRecieved = _.debounce(function() {
                    var $popover = $menu.find('.new-email-notification');
                    if ($popover.length > 0) {
                        if ($menu.hasClass('open') === false) {
                            $popover.show().delay(5000).fadeOut(1000);
                        }
                    } else {
                        messenger.notificationMessage('success', __('oro.email.notification.new_email'));
                    }
                }, 6000, true);
                if ($menu.length !== 0) {
                    var options = $menu.data('page-component-options');
                    options._sourceElement = $menu.find('.dropdown-menu');
                    options._iconElement = $menu.find('.email-notification-icon');
                    options.countModel = new EmailNotificationCountModel({'unreadEmailsCount': options.count});
                    this.component = new EmailNotificationComponent(options);
                }
                sync.subscribe(channel, function(response) {
                    var message = JSON.parse(response);
                    if (_.result(message, 'hasNewEmail') === true) {
                        onNewEmailRecieved();
                    }
                });
            }
        });
    });
});
