/*global require*/

/**
 * Init PageView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page-view'
], function (BaseController, PageView) {
    'use strict';
    BaseController.addBeforeActionReuse('page', PageView);
});

/**
 * Init PageBreadcrumbView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/breadcrumb-view'
], function (BaseController, PageBreadcrumbView) {
    'use strict';
    BaseController.addBeforeActionReuse('breadcrumb', PageBreadcrumbView, {
        el: 'breadcrumb'
    });
});

/**
 * Init PageContentView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/content-view'
], function (BaseController, PageContentView) {
    'use strict';
    BaseController.addBeforeActionReuse('content', PageContentView, {
        el: 'mainContainer'
    });
});

/**
 * Init PageMainMenuView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/main-menu-view'
], function (BaseController, PageMainMenuView) {
    'use strict';
    BaseController.addBeforeActionReuse('mainMenu', PageMainMenuView, {
        el: 'mainMenu'
    });
});

/**
 * Init PageUserMenuView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/user-menu-view'
], function (BaseController, PageUserMenuView) {
    'use strict';
    BaseController.addBeforeActionReuse('userMenu', PageUserMenuView, {
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
