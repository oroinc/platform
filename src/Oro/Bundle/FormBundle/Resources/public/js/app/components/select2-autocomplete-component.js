define(function (require) {
    'use strict';
    var Select2AutocompleteComponent,
        Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view'),
        Select2Component = require('./select2-component');
    Select2AutocompleteComponent = Select2Component.extend({
        ViewType: Select2AutocompleteView
    });
    return Select2AutocompleteComponent;
});
