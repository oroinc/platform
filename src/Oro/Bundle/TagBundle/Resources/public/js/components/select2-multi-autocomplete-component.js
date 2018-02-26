define(function(require) {
    'use strict';

    var Select2MultiAutocompleteComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2MultiAutocompleteComponent = Select2AutocompleteComponent.extend({
        oroTagCreateGranted: false,

        /**
         * @inheritDoc
         */
        constructor: function Select2MultiAutocompleteComponent() {
            Select2MultiAutocompleteComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.oroTagCreateGranted = _.result(options, 'oro_tag_create_granted') || this.oroTagCreateGranted;
            Select2MultiAutocompleteComponent.__super__.initialize.call(this, options);
        },

        preConfig: function(config) {
            var self = this;
            Select2MultiAutocompleteComponent.__super__.preConfig.call(this, config);
            config.maximumInputLength = 50;

            config.createSearchChoice = function(term, data) {
                var match = _.find(data, function(item) {
                    return item.name.toLowerCase().localeCompare(term.toLowerCase()) === 0;
                });
                if (typeof match === 'undefined' && self.oroTagCreateGranted) {
                    return {
                        id: JSON.stringify({
                            id: term,
                            name: term
                        }),
                        name: term,
                        isNew: true
                    };
                }
                return null;
            };

            if (!this.oroTagCreateGranted) {
                config.placeholder = __('oro.tag.form.choose_tag');
            }

            return config;
        }
    });
    return Select2MultiAutocompleteComponent;
});
