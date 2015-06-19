define(function (require) {
    'use strict';
    var Select2MultiAutocompleteComponent,
        $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        Select2AutocompleteComponent = require('oro/select2-autocomplete-component');
    Select2MultiAutocompleteComponent = Select2AutocompleteComponent.extend({
        oroTagCreateGranted: false,
        initialize: function (options) {
            this.oroTagCreateGranted = _.result(options, 'oro_tag_create_granted') || this.oroTagCreateGranted;
            Select2MultiAutocompleteComponent.__super__.initialize.call(this, options);
        },
        preConfig: function (config) {
            var that = this;
            Select2MultiAutocompleteComponent.__super__.preConfig.call(this, config);
            config.maximumInputLength = 50;

            config.createSearchChoice = function(term, data) {
                if (
                    $(data).filter(function() {
                        return this.name.toLowerCase().localeCompare(term.toLowerCase()) === 0;
                    }).length === 0 && that.oroTagCreateGranted
                ) {
                    return {
                        id: term,
                        name: term
                    };
                }
                return null;
            }

            if (!this.oroTagCreateGranted) {
                config.placeholder = __('oro.tag.form.choose_tag');
            }

            return config;
        }
    });
    return Select2MultiAutocompleteComponent;
});
