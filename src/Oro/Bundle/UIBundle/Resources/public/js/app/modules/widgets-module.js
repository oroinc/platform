define(function(require) {
    'use strict';

    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');

    /**
     * Init Widget Manager's handlers and listeners
     */
    mediator.setHandler('widgets:getByIdAsync', widgetManager.getWidgetInstance, widgetManager);
    mediator.setHandler('widgets:getByAliasAsync', widgetManager.getWidgetInstanceByAlias, widgetManager);
    mediator.on('widget_initialize', widgetManager.addWidgetInstance, widgetManager);
    mediator.on('widget_remove', widgetManager.removeWidget, widgetManager);
    mediator.on('page:afterChange', widgetManager.resetWidgets, widgetManager);
});

