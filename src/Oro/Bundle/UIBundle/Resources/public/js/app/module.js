/*global require*/
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page-view',
    'oroui/js/app/views/page/breadcrumb-view',
    'oroui/js/app/views/page/content-view',
    'oroui/js/app/views/page/main-menu-view',
    'oroui/js/app/views/page/user-menu-view',
], function (BaseController, PageView, BreadcrumbView, ContentView, MainMenuView, UserMenuView) {
    'use strict';

    BaseController.addBeforeActionReuse('page', PageView);

    BaseController.addBeforeActionReuse('breadcrumb', BreadcrumbView, {
        el: 'breadcrumb'
    });

    BaseController.addBeforeActionReuse('content', ContentView, {
        el: 'mainContainer'
    });

    BaseController.addBeforeActionReuse('mainMenu', MainMenuView, {
        el: 'mainMenu'
    });

    BaseController.addBeforeActionReuse('userMenu', UserMenuView, {
        el: 'userMenu'
    });
});
