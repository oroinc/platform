require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery',
        'oroemail/js/app/components/email-notification-component',
        'oroemail/js/app/components/new-email-message-component',
        'oroemail/js/app/models/email-notification/email-notification-count-model'
    ], function($, EmailNotificationComponent, NewEmailMessageComponent, EmailNotificationCountModel) {
        BaseController.addToReuse('emailNotification', {
            compose: function() {
                var $menu = $('.email-notification-menu');
                var $notification = $menu.find('.new-email-notification');
                if ($menu.length !== 0) {
                    var options = $menu.data('page-component-options');
                    options._sourceElement = $menu.find('.dropdown-menu');
                    options._iconElement = $menu.find('.email-notification-icon');
                    options.countModel = new EmailNotificationCountModel({'unreadEmailsCount': options.count});
                    this.component = new EmailNotificationComponent(options);
                }
                this.emailNotificationComponent = new NewEmailMessageComponent({
                    notificationElement: $notification.length > 0 ? $notification : null
                });
            }
        });
    });
});
