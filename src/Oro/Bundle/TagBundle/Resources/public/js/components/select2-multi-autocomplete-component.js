define(function (require) {
    'use strict';
    var Select2MultiAutocompleteComponent,
        $ = require('jquery'),
        __ = require('orotranslation/js/translator'),
        Select2AutocompleteComponent = require('oroform/js/app/components/select2-autocomplete-component');
    Select2MultiAutocompleteComponent = Select2AutocompleteComponent.extend({
        processExtraConfig: function (select2Config, params) {
            Select2MultiAutocompleteComponent.__super__.processExtraConfig(select2Config, params);
            select2Config.maximumInputLength = 50;

            select2Config.createSearchChoice = function(term, data) {
                if (
                    $(data).filter(function() {
                        return this.name.toLowerCase().localeCompare(term.toLowerCase()) === 0;
                    }).length === 0 && params.oroTagCreateGranted
                ) {
                    return {
                        id: term,
                        name: term
                    };
                }
                return null;
            }

            if (!params.oroTagCreateGranted) {
                select2Config.placeholder = __('oro.tag.form.choose_tag');
            }

            return select2Config;
        }
    });
    return Select2MultiAutocompleteComponent;
});
