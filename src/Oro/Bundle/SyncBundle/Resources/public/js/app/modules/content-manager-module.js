define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var contentManager = require('orosync/js/content-manager');

    /**
     * Init ContentManager's handlers
     */
    mediator.setHandler('pageCache:init', contentManager.init, contentManager);
    mediator.setHandler('pageCache:getCurrent', contentManager.getCurrent, contentManager);
    mediator.setHandler('pageCache:add', contentManager.add, contentManager);
    mediator.setHandler('pageCache:get', contentManager.get, contentManager);
    mediator.setHandler('pageCache:remove', contentManager.remove, contentManager);
    mediator.setHandler('pageCache:state:save', contentManager.saveState, contentManager);
    mediator.setHandler('pageCache:state:fetch', contentManager.fetchState, contentManager);
    mediator.setHandler('pageCache:state:check', contentManager.checkState, contentManager);
    mediator.setHandler('compareUrl', contentManager.compareUrl, contentManager);
    mediator.setHandler('currentUrl', contentManager.currentUrl, contentManager);
    mediator.setHandler('changeUrl', contentManager.changeUrl, contentManager);
    mediator.setHandler('changeUrlParam', contentManager.changeUrlParam, contentManager);
});
