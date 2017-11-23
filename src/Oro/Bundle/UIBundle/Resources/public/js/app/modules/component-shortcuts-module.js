define(function(require) {
    'use strict';

    var ComponentShortcutsManager = require('oroui/js/component-shortcuts-manager');

    ComponentShortcutsManager.add('module', {});

    ComponentShortcutsManager.add('view', {
        moduleName: 'oroui/js/app/components/view-component',
        scalarOption: 'view'
    });

    ComponentShortcutsManager.add('viewport', {
        moduleName: 'oroui/js/app/components/viewport-component'
    });

    ComponentShortcutsManager.add('jquery', {
        moduleName: 'oroui/js/app/components/jquery-widget-component',
        scalarOption: 'widgetModule'
    });

    ComponentShortcutsManager.add('collapse', {
        moduleName: 'oroui/js/app/components/jquery-widget-component',
        options: {
            widgetModule: 'oroui/js/widget/collapse-widget'
        }
    });
});
