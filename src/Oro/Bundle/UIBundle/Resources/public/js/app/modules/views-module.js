define([
    'oroui/js/app/controllers/base/controller',
    'oroui/js/app/views/page-view',
    'oroui/js/tools',
    'underscore',
    'module'
], function(BaseController, PageView, tools, _, module) {
    'use strict';

    var config = module.config();
    config = _.extend({
        showLoadingMaskOnStartup: true
    }, config);

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
            beforeContentAddition: '#before-content-addition',
            messages: '#flash-messages .flash-messages-holder'
        }
    });

    /**
     * Init PageContentView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/content-view'
    ], function(PageContentView) {
        BaseController.addToReuse('content', PageContentView, {
            el: 'region:mainContainer'
        });
    });

    /**
     * Init PageBeforeContentAdditionView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/before-content-addition-view'
    ], function(PageBeforeContentAdditionView) {
        BaseController.addToReuse('beforeContentAddition', PageBeforeContentAdditionView, {
            el: 'region:beforeContentAddition'
        });
    });

    /**
     * Init PageMainMenuView and BreadcrumbView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/main-menu-view',
        'oroui/js/app/views/page/breadcrumb-view'
    ], function(PageMainMenuView, BreadcrumbView) {
        BaseController.addToReuse('breadcrumb', BreadcrumbView, {
            el: 'region:breadcrumb'
        });
        BaseController.addToReuse('mainMenu', PageMainMenuView, {
            el: 'region:mainMenu'
        });
    });

    /**
     * Init PageUserMenuView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/user-menu-view'
    ], function(PageUserMenuView) {
        BaseController.addToReuse('userMenu', PageUserMenuView, {
            el: 'region:userMenu'
        });
    });

    /**
     * Init PageLoadingMaskView
     */
    BaseController.loadBeforeAction([
        'oroui/js/mediator',
        'oroui/js/app/views/loading-mask-view'
    ], function(mediator, LoadingMaskView) {
        BaseController.addToReuse('loadingMask', {
            compose: function() {
                this.view = new LoadingMaskView({
                    container: 'body',
                    hideDelay: 25
                });
                mediator.setHandler('showLoading', this.view.show, this.view);
                mediator.setHandler('hideLoading', this.view.hide, this.view);
                mediator.on('page:beforeChange', this.view.show, this.view);
                mediator.on('page:afterChange', this.view.hide, this.view);
                if (config.showLoadingMaskOnStartup) {
                    this.view.show();
                }
            }
        });
    });

    /**
     * Init PageMessagesView
     */
    BaseController.loadBeforeAction([
        'oroui/js/app/views/page/messages-view'
    ], function(PageMessagesView) {
        BaseController.addToReuse('messages', PageMessagesView, {
            el: 'region:messages'
        });
    });

    if (tools.debug) {
        /**
         * Init DebugToolbarView
         */
        BaseController.loadBeforeAction([
            'oroui/js/mediator',
            'oroui/js/app/views/page/debug-toolbar-view'
        ], function(mediator, DebugToolbarView) {
            BaseController.addToReuse('debugToolbar', {
                compose: function() {
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
    } else {
        // initialize updateDebugToolbar mediator handler
        BaseController.loadBeforeAction([
            'oroui/js/mediator'
        ], function(mediator) {
            mediator.setHandler('updateDebugToolbar', _.noop);
        });
    }
});
