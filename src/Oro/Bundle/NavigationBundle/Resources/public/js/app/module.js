/*global require*/
require([
    'oroui/js/app/controllers/base/controller'
], function (BaseController) {
    'use strict';

    /**
     * Init PageHistoryView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/page/history-view'
    ], function (PageHistoryView) {
        BaseController.addToReuse('history', PageHistoryView, {
            el: '#history-content'
        });
    });

    /**
     * Init PageMostViewedView
     */
    BaseController.loadBeforeAction([
        'oronavigation/js/app/views/page/most-viewed-view'
    ], function (PageMostViewedView) {
        BaseController.addToReuse('mostViewed', PageMostViewedView, {
            el: '#mostviewed-content'
        });
    });

    /**
     * Init Favorite related views
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/views/page/favorite/view',
        'oronavigation/js/app/models/base/model',
        'oroui/js/app/models/base/collection'
    ], function ($, FavoriteView, Model, Collection) {
        var collection;

        collection = new Collection([], {
            model: Model
        });

        BaseController.addToReuse('pageFavorite', FavoriteView, {
            el: 'body',
            keepElement: true,
            dataSource: '#favorite-content [data-data]',
            regions: {
                pinButton: '#pin-button-div .favorite-button',
                pinTab: '#favorite-content'
            },
            tabItemTemplate: $('#template-dot-menu-item').html(),
            tabOptions: {
                listSelector: '.extra-list',
                fallbackSelector: '.dot-menu-empty-message'
            },
            collection: collection
        });
    });

    /**
     * Init PinBar related views
     */
    BaseController.loadBeforeAction([
        'jquery',
        'oronavigation/js/app/views/page/pin/view',
        'oronavigation/js/app/models/base/model',
        'oroui/js/app/models/base/collection'
    ], function ($, PinView, Model, Collection) {
        var collection;

        collection = new Collection([], {
            model: Model
        });

        BaseController.addToReuse('pagePin', PinView, {
            el: 'body',
            keepElement: true,
            dataSource: '#pinbar-content [data-data]',
            regions: {
                pinButton: '#pin-button-div .minimize-button',
                pinTab: '#pinbar-content',
                pinBar: '.list-bar'
            },
            tabItemTemplate: $('#template-dot-menu-item').html(),
            tabOptions: {
                listSelector: '.extra-list',
                fallbackSelector: '.dot-menu-empty-message'
            },
            barItemTemplate: $('#template-list-pin-item').html(),
            barOptions: {
                listSelector: 'ul',
                fallbackSelector: '.pin-bar-empty'
            },
            collection: collection
        });
    });
});


/**
 * Init ContentManager
 */
require([
    'oroui/js/mediator',
    'oronavigation/js/content-manager'
], function (mediator, contentManager) {
    'use strict';

    mediator.setHandler('pageCache:init', contentManager.init);
    mediator.setHandler('pageCache:add', contentManager.add);
    mediator.setHandler('pageCache:get', contentManager.get);
    mediator.setHandler('pageCache:remove', contentManager.remove);
    mediator.setHandler('pageCache:state:save', contentManager.saveState);
    mediator.setHandler('pageCache:state:fetch', contentManager.fetchState);
    mediator.setHandler('compareUrl', contentManager.compareUrl);
    mediator.setHandler('currentUrl', contentManager.currentUrl);
});
