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
