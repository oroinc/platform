require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery',
        'oroemail/js/app/components/email-notification-component',
        'oroemail/js/app/models/email-notification/email-notification-count-model'
    ], function($, EmailNotificationComponent, EmailNotificationCountModel) {
        BaseController.addToReuse('emailNotification', {
            compose: function() {
                var $menu = $('.email-notification-menu');
                if ($menu.length !== 0) {
                    var options = $menu.data('page-component-options');
                    options._sourceElement = $menu.find('.dropdown-menu');
                    options._iconElement = $menu.find('.email-notification-icon');
                    options.countModel = new EmailNotificationCountModel({'unreadEmailsCount': options.count});
                    this.component = new EmailNotificationComponent(options);
                }
            }
        });
    });

    BaseController.loadBeforeAction([
        'jquery',
        'oroemail/js/app/components/new-email-message-component'
    ], function($, NewEmailMessageComponent) {
        BaseController.addToReuse('mewEmailMessage', {
            compose: function() {
                var $notification = $('.email-notification-menu .new-email-notification');
                this.component = new NewEmailMessageComponent({
                    notificationElement: $notification.length > 0 ? $notification : null
                });
            }
        });
    });
});
