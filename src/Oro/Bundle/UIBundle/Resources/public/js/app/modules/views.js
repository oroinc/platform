/*global require*/
require([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page-view'
], function (BaseController, PageView) {
    'use strict';

    /**
     * Init PageView
     */
    BaseController.addToReuse('page', PageView, {
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
        'oroui/js/mediator',
        'oroui/js/app/views/page/loading-mask-view'
    ], function (mediator, PageLoadingMaskView) {
        BaseController.addToReuse('loadingMask', {
            compose: function () {
                var view;
                view = new PageLoadingMaskView({
                    autoRender: true,
                    region: 'loadingMask'
                });
                mediator.setHandler('showLoading', view.show, view);
                mediator.setHandler('hideLoading', view.hide, view);
                this.view = view;
            }
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
                var view;
                if (!mediator.execute('retrieveOption', 'debug')) {
                    return;
                }
                view = new DebugToolbarView({
                    el: 'body .sf-toolbar'
                });
                mediator.setHandler('updateDebugToolbar', view.updateToolbar, view);
                this.view = view;
            }
        });
    });
});
