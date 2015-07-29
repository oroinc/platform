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
                var options = jquery('.email-notification-menu').data('page-component-options');
                options._sourceElement = '.email-notification-menu';
                this.cComponent = new EmailNotificationComponent(options);
            }
        });
    });
});
