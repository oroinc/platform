/*global require*/
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page-view'
], function (BaseController, PageView) {
    'use strict';
    BaseController.addBeforeActionReuse('page', PageView);
});

/**
 * Init BreadcrumbView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/breadcrumb-view'
], function (BaseController, BreadcrumbView) {
    'use strict';
    BaseController.addBeforeActionReuse('breadcrumb', BreadcrumbView, {
        el: 'breadcrumb'
    });
});

/**
 * Init ContentView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/content-view'
], function (BaseController, ContentView) {
    'use strict';
    BaseController.addBeforeActionReuse('content', ContentView, {
        el: 'mainContainer'
    });
});

/**
 * Init MainMenuView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/main-menu-view'
], function (BaseController, MainMenuView) {
    'use strict';
    BaseController.addBeforeActionReuse('mainMenu', MainMenuView, {
        el: 'mainMenu'
    });
});

/**
 * Init UserMenuView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/user-menu-view'
], function (BaseController, UserMenuView) {
    'use strict';
    BaseController.addBeforeActionReuse('userMenu', UserMenuView, {
        el: 'userMenu'
    });
});

/**
 * Init PageLoadingMaskView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/loading-mask-view'
], function (BaseController, PageLoadingMaskView) {
    'use strict';
    BaseController.addBeforeActionReuse('loadingMask', PageLoadingMaskView, {
        region: 'loadingMask'
    });
});
