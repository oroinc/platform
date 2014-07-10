/*global require*/
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
    mediator.setHandler('pageCache:state:check', contentManager.checkState);
    mediator.setHandler('compareUrl', contentManager.compareUrl);
    mediator.setHandler('currentUrl', contentManager.currentUrl);
});
