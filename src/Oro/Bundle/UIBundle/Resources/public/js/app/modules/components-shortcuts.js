define(function(require) {
    'use strict';

    var ComponentsShortcutsManager = require('oroui/js/components-shortcuts-manager');

    ComponentsShortcutsManager.add('expand-text', {
        options: {
            widgetModule: 'orofrontend/default/js/widgets/expand-text-widget'
        },
        moduleName: 'oroui/js/app/components/jquery-widget-component'
    });

    ComponentsShortcutsManager.add('collapse', {
        options: {
            widgetModule: 'oroui/js/widget/collapse-widget'
        },
        moduleName: 'oroui/js/app/components/jquery-widget-component'
    });

    return ComponentsShortcutsManager;
});
