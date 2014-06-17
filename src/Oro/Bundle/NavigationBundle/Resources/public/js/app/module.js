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
 * Init PinBar and Favorite related views
 */
require([
    'oroui/js/app/controllers/base/controller',
    'oronavigation/js/app/views/page/pin-buttons-view'
], function (BaseController, PagePinButtonsView) {
    'use strict';
    BaseController.addBeforeActionReuse('pinButtons', PagePinButtonsView, {
        el: '#pin-button-div',
        buttons: '.minimize-button, .favorite-button'
    });
});

/**
 * Init ContentManager
 */
require([
    'chaplin',
    'oronavigation/js/content-manager'
], function (Chaplin, contentManager) {
    'use strict';

    Chaplin.mediator.setHandler('pageCache:init', contentManager.init);
    Chaplin.mediator.setHandler('pageCache:add', contentManager.add);
    Chaplin.mediator.setHandler('pageCache:get', contentManager.get);
    Chaplin.mediator.setHandler('pageCache:remove', contentManager.remove);
    Chaplin.mediator.setHandler('pageCache:state:save', contentManager.saveState);
    Chaplin.mediator.setHandler('pageCache:state:fetch', contentManager.fetchState);
});
