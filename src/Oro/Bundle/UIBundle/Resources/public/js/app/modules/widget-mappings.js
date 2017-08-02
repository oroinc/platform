define(function(require) {
    'use strict';

    var WidgetMappingManager = require('oroui/js/widget-mapping-manager');

    WidgetMappingManager.addMapping({
        selector: '[data-expand-text]',
        dataAttribute: 'expandText',
        options: {
            widgetModule: 'orofrontend/default/js/widgets/expand-text-widget'
        },
        Module: 'oroui/js/app/components/jquery-widget-component'
    });

    WidgetMappingManager.addMapping({
        selector: '[data-page-component-module]'
    });

    return WidgetMappingManager;
});
