define([
    'oroui/js/mediator',
    'oroui/js/app/controllers/base/controller'
], function(mediator, BaseController) {
    'use strict';

    /**
     * Init Widget Manager's handlers and listeners
     */
    BaseController.loadBeforeAction([
        'oroui/js/widget-manager'
    ], function(manager) {
        mediator.setHandler('widgets:getByIdAsync', manager.getWidgetInstance, manager);
        mediator.setHandler('widgets:getByAliasAsync', manager.getWidgetInstanceByAlias, manager);

        mediator.on('widget_initialize', manager.addWidgetInstance, manager);
        mediator.on('widget_remove', manager.removeWidget, manager);
        mediator.on('page:afterChange', manager.resetWidgets, manager);
    });
});

