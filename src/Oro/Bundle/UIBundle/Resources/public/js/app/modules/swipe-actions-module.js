define(function(require) {
    'use strict';

    var BaseController = require('oroui/js/app/controllers/base/controller');
    /**
     * Init layout's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroui/js/swipe-action-manager'
    ], function(SwipeActionsManager) {
        new SwipeActionsManager('body');
    });
});
