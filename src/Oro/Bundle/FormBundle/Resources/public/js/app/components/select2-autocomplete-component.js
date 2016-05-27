define(function(require) {
    'use strict';

    var Select2AutocompleteComponent;
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    var Select2Component = require('oro/select2-component');

    Select2AutocompleteComponent = Select2Component.extend({
        ViewType: Select2AutocompleteView,
        setConfig: function(config) {
            config = Select2AutocompleteComponent.__super__.setConfig.apply(this, arguments);
            var propName = config.propertyNameForNewItem;
            if (propName) {
                config.createSearchChoice = function(value, results) {
                    if (results.length === 0) {
                        var item = {id: null};
                        item[propName] = value;
                        return item;
                    }
                };
                config.id = function(e) {
                    var val = {id: e.id};
                    if (val.id === null) {
                        val.value = e[propName];
                    }
                    return JSON.stringify(val);
                };
            }
            return config;
        }
    });

    return Select2AutocompleteComponent;
});
