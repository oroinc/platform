define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    const Select2MultiAutocompleteComponent = Select2AutocompleteComponent.extend({
        oroTagCreateGranted: false,

        /**
         * @inheritdoc
         */
        constructor: function Select2MultiAutocompleteComponent(options) {
            Select2MultiAutocompleteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.oroTagCreateGranted = _.result(options, 'oro_tag_create_granted') || this.oroTagCreateGranted;
            Select2MultiAutocompleteComponent.__super__.initialize.call(this, options);
        },

        preConfig: function(config) {
            const self = this;
            Select2MultiAutocompleteComponent.__super__.preConfig.call(this, config);
            config.maximumInputLength = 50;

            config.createSearchChoice = function(term, data) {
                const match = _.find(data, function(item) {
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
