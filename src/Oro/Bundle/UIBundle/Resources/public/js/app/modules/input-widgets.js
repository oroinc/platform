define(function(require) {
    'use strict';

    var InputWidgetManager = require('oroui/js/input-widget-manager');
    var UniformSelectInputWidget = require('oroui/js/app/views/input-widget/uniform-select');
    var UniformFileInputWidget = require('oroui/js/app/views/input-widget/uniform-file');

    InputWidgetManager.addWidget('uniform-select', {
        selector: 'select:not(.no-uniform):not([multiple])',
        Widget: UniformSelectInputWidget
    });

    InputWidgetManager.addWidget('uniform-file', {
        selector: 'input:file',
        Widget: UniformFileInputWidget
    });
});
