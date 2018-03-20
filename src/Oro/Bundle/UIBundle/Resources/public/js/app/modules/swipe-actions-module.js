define(function(require) {
    'use strict';

    var BaseController = require('oroui/js/app/controllers/base/controller');
    var _ = require('underscore');
    /**
     * Init layout's handlers and listeners
     */
    if (_.isMobile()) {
        BaseController.loadBeforeAction([
            'oroui/js/swipe-action-manager'
        ], function(SwipeActionsManager) {
            new SwipeActionsManager('body');
        });
    }
});
