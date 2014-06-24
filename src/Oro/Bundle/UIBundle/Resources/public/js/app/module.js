/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function (BaseController) {
    'use strict';

    /**
     * Init PageView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/base/view'
    ], function (BaseView) {
        BaseController.addToReuse('page', BaseView, {
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
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/breadcrumb-view'
    ], function (PageBreadcrumbView) {
        BaseController.addToReuse('breadcrumb', PageBreadcrumbView, {
            el: 'breadcrumb'
        });
    });

    /**
     * Init PageContentView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/content-view'
    ], function (PageContentView) {
        BaseController.addToReuse('content', PageContentView, {
            el: 'mainContainer'
        });
    });

    /**
     * Init PageMainMenuView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/main-menu-view'
    ], function (PageMainMenuView) {
        BaseController.addToReuse('mainMenu', PageMainMenuView, {
            el: 'mainMenu'
        });
    });

    /**
     * Init PageUserMenuView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/user-menu-view'
    ], function (PageUserMenuView) {
        BaseController.addToReuse('userMenu', PageUserMenuView, {
            el: 'userMenu'
        });
    });

    /**
     * Init PageLoadingMaskView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/loading-mask-view'
    ], function (PageLoadingMaskView) {
        BaseController.addToReuse('loadingMask', PageLoadingMaskView, {
            region: 'loadingMask'
        });
    });

    /**
     * Init PageMessagesView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/messages-view'
    ], function (PageMessagesView) {
        BaseController.addToReuse('messages', PageMessagesView, {
            el: 'messages'
        });
    });

    /**
     * Init DebugToolbarView
     */
    BaseController.loadBeforeAction([
        'oroui/js/mediator',
        'oroui/js/app/views/page/debug-toolbar-view'
    ], function (mediator, DebugToolbarView) {
        BaseController.addToReuse('debugToolbar', {
            compose: function () {
                if (!mediator.execute('retrieveOption', 'debug')) {
                    return;
                }
                this.view = new DebugToolbarView({
                    el: 'body .sf-toolbar'
                });
            }
        });
    });

});
