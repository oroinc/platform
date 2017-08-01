define(function(require) {
    'use strict';

    var SimpleWidgetManager = require('oroui/js/simple-widget-manager');

    SimpleWidgetManager.addWidget({
        selector: '.expand-text:not(.init)',
        data: 'expandText',
        Widget: 'orofrontend/default/js/widgets/expand-text-widget',
        Module: 'oroui/js/app/components/jquery-widget-component'
    });

    return SimpleWidgetManager;
});
