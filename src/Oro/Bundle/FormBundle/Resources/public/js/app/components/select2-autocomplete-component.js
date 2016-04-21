define(function(require) {
    'use strict';

    var Select2AutocompleteComponent;
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    var Select2Component = require('oro/select2-component');

    Select2AutocompleteComponent = Select2Component.extend({
        ViewType: Select2AutocompleteView,
        setConfig: function(config) {
            config = Select2AutocompleteComponent.__super__.setConfig.apply(this, arguments);
            /* Next option says that select2 has to propose to select new item if value in search field wasn't found
             * We need to have a name of property which used in option template to be able display new item correctly
             */
            var propName = config.propertyNameForNewItem;
            if (propName) {
                config.createSearchChoice = function(value, results) {
                    if (results.length === 0) {
                        var item = {id: null};
                        item[propName] = value;

                        return item;
                    }
                };
                /* In case we can create new items we can't use plain id in input value because a new item hasn't it yet
                 * So value is a JSON with id property and value property for a new item. For instance, {id: 123} for
                 * existing item and {id: null, value: "My new item"} for new one
                 */
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
