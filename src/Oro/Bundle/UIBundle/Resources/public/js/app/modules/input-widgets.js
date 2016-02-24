define(function(require) {
    'use strict';

    var InputWidgetManager = require('oroui/js/input-widget-manager');
    var UniformSelectInputWidget = require('oroui/js/app/views/input-widget/uniform-select');
    var UniformFileInputWidget = require('oroui/js/app/views/input-widget/uniform-file');

    InputWidgetManager.registerWidget({
        tagName: 'SELECT',
        selector: 'select:not(.no-uniform)',
        Widget: UniformSelectInputWidget
    });

    InputWidgetManager.registerWidget({
        tagName: 'INPUT',
        selector: 'input:file',
        Widget: UniformFileInputWidget
    });
});
