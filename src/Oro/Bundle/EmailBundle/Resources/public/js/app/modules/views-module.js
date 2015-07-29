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
        'oroemail/js/app/components/email-notification-component'
    ], function(EmailNotificationComponent) {
        BaseController.addToReuse('email-notification', EmailNotificationComponent, {});
    });
});
