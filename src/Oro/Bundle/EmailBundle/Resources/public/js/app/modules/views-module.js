define([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    BaseController.loadBeforeAction([
        'jquery',
        'oroemail/js/app/components/user-menu-email-notification-component'
    ], function($, UserMenuEmailNotificationComponent) {
        BaseController.addToReuse('emailNotification', {
            compose: function() {
                var $menu = $('.email-notification-menu');
                if ($menu.length !== 0) {
                    var options = $menu.data('page-component-options');
                    options._sourceElement = $menu.find('.dropdown-menu');
                    options._iconElement = $menu.find('.email-notification-icon');
                    this.component = new UserMenuEmailNotificationComponent(options);
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
