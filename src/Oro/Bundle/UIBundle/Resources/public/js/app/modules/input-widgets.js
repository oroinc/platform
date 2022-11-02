import InputWidgetManager from 'oroui/js/input-widget-manager';
import UniformSelectInputWidget from 'oroui/js/app/views/input-widget/uniform-select';
import UniformFileInputWidget from 'oroui/js/app/views/input-widget/uniform-file';
import Select2InputWidget from 'oroui/js/app/views/input-widget/select2';
import NumberInputWidget from 'oroui/js/app/views/input-widget/number';
import ClearableInputWidget from 'oroui/js/app/views/input-widget/clearable';

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

InputWidgetManager.addWidget('clearable', {
    selector: 'input[data-clearable]',
    Widget: ClearableInputWidget
});
