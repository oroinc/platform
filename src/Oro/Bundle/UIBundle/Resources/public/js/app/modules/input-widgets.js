define(function(require) {
    'use strict';

    var InputWidgetManager = require('oroui/js/input-widget-manager');
    var UniformSelectInputWidget = require('oroui/js/app/views/input-widget/uniform-select');
    var UniformFileInputWidget = require('oroui/js/app/views/input-widget/uniform-file');
    var Select2InputWidget = require('oroui/js/app/views/input-widget/select2');
    var NumberInputWidget = require('oroui/js/app/views/input-widget/number');
    var CheckboxInputWidget = require('oroui/js/app/views/input-widget/checkbox');
    var ClearableInputWidget = require('oroui/js/app/views/input-widget/clearable');

    InputWidgetManager.addWidget('uniform-select', {
        selector: 'select:not(.no-uniform):not([multiple])',
        Widget: UniformSelectInputWidget
    });

    InputWidgetManager.addWidget('uniform-file', {
        selector: 'input:file:not(.no-uniform)',
        Widget: UniformFileInputWidget
    });

    InputWidgetManager.addWidget('select2', {
        selector: 'select,input',
        disableAutoCreate: true,
        Widget: Select2InputWidget
    });

    InputWidgetManager.addWidget('number', {
        selector: 'input[type="number"]',
        Widget: NumberInputWidget
    });

    InputWidgetManager.addWidget('checkbox', {
        selector: 'input[type="checkbox"]:not(.invisible, .hide),input[type="radio"]:not(.invisible, .hide)',
        Widget: CheckboxInputWidget
    });

    InputWidgetManager.addWidget('clearable', {
        selector: 'input[data-clearable]',
        Widget: ClearableInputWidget
    });
});
