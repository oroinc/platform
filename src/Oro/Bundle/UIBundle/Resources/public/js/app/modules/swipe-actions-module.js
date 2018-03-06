define(function(require) {
    'use strict';

    var BaseController = require('oroui/js/app/controllers/base/controller');
    /**
     * Init layout's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'jquery-touchswipe',
        'oroui/js/swipe-action-manager'
    ], function(jqueryTouchswipe, SwipeActionsManager) {
        new SwipeActionsManager();
    });
});
