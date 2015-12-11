require([
    'oroui/js/app/controllers/base/controller'
], function(BaseController) {
    'use strict';

    /**
     * Init ShortcutsView
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oroemail/js/app/components/email-notification-component'
    ], function($, EmailNotificationComponent) {
        BaseController.addToReuse('emailNotification', {
            compose: function() {
                var $menu = $('.email-notification-menu');
                if ($menu.length !== 0) {
                    var options = $menu.data('page-component-options');
                    options._sourceElement = $menu;
                    this.component = new EmailNotificationComponent(options);
                }
            }
        });
    });
});
