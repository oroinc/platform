define(function(require) {
    'use strict';

    var Select2AutocompleteComponent;
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    var Select2Component = require('oro/select2-component');

    Select2AutocompleteComponent = Select2Component.extend({
        ViewType: Select2AutocompleteView
    });

    return Select2AutocompleteComponent;
});
