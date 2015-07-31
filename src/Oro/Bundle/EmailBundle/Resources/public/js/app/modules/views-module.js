/*global require*/
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/tools'
], function(BaseController, tools) {
    'use strict';

    if (tools.isMobile()) {
        return;
    }

    /**
     * Init ShortcutsView
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oroemail/js/app/components/email-notification-component'
    ], function(jquery, EmailNotificationComponent) {
        BaseController.addToReuse('emailNotification', {
            compose: function() {
                var $menu = jquery('.email-notification-menu');
                if ($menu.length !== 0) {
                    var options = $menu.data('page-component-options');
                    options._sourceElement = '.email-notification-menu';
                    this.cComponent = new EmailNotificationComponent(options);
                }
            }
        });
    });
});
