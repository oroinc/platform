/*global require*/

/**
 * Init PageView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/base/view'
], function (BaseController, BaseView) {
    'use strict';
    BaseController.addBeforeActionReuse('page', BaseView, {
        el: 'body',
        keepElement: true,
        regions: {
            mainContainer: '#container',
            mainMenu: '#main-menu',
            userMenu: '#top-page .user-menu',
            breadcrumb: '#breadcrumb',
            loadingMask: '#main .hash-loading-mask',
            messages: '#flash-messages .flash-messages-holder'
        }
    });
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

/**
 * Init PageMessagesView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page/messages-view'
], function (BaseController, PageMessagesView) {
    'use strict';
    BaseController.addBeforeActionReuse('messages', PageMessagesView, {
        el: 'messages'
    });
});
