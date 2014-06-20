/*global require*/

/**
 * Init PageHistoryView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oronavigation/js/app/views/page/history-view'
], function (BaseController, PageHistoryView) {
    'use strict';
    BaseController.addBeforeActionReuse('history', PageHistoryView, {
        el: '#history-content'
    });
});

/**
 * Init PageMostViewedView
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oronavigation/js/app/views/page/most-viewed-view'
], function (BaseController, PageMostViewedView) {
    'use strict';
    BaseController.addBeforeActionReuse('mostViewed', PageMostViewedView, {
        el: '#mostviewed-content'
    });
});

/**
 * Init Favorite related views
 */
/*require([
    'oroui/js/app/controllers/base/controller',
    'oronavigation/js/app/views/page/favorite-button-view',
    'oronavigation/js/app/models/favorite-model',
    'oronavigation/js/app/models/favorite-collection'
], function (BaseController, FavoriteButtonView, FavoriteModel, FavoriteCollection) {
    'use strict';
    var favoriteCollection = new FavoriteCollection({
        model: FavoriteModel
    });

    BaseController.addBeforeActionReuse('favoriteButton', FavoriteButtonView, {
        el: '#pin-button-div .favorite-button',
        collection: favoriteCollection
    });
});*/

/**
 * Init PinBar related views
 */
require([
    'jquery',
    'oroui/js/app/controllers/base/controller',
    'oronavigation/js/app/views/page/pin/view',
    'oronavigation/js/app/models/pin-model',
    'oroui/js/app/models/base/collection'
], function ($, BaseController, PinView, Model, Collection) {
    'use strict';
    var collection;

    collection = new Collection([], {
        model: Model
    });

    BaseController.addBeforeActionReuse('pagePin', PinView, {
        el: 'body',
        keepElement: true,
        dataSource: '#pinbar-content [data-data]',
        regions: {
            pinButton: '#pin-button-div .minimize-button',
            pinTab: '#pinbar-content',
            pinBar: '.list-bar'
        },
        tabItemTemplate: $('#template-tab-pin-item').html(),
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
