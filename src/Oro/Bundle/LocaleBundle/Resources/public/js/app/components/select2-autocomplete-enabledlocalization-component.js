define(function(require) {
    'use strict';

    var Select2AutocompleteEnabledLocalizationComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AutocompleteEnabledLocalizationComponent = Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            enabledLocalizationSelect: '[name$="[enabledLocalization]"]',
            websiteSelect: '[name$="[website]"]',
            datagridName: 'enabled-localizations-select-grid',
            delimiter: ';'
        },

        /**
         * @property {Object}
         */
        $enabledLocalizationSelect: null,

        /**
         * @property {Object}
         */
        $websiteSelect: null,

        /**
         * @inheritDoc
         */
        constructor: function Select2AutocompleteEnabledLocalizationComponent() {
            Select2AutocompleteEnabledLocalizationComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            Select2AutocompleteEnabledLocalizationComponent.__super__.initialize.call(this, options);

            this.$websiteSelect = $(this.options.websiteSelect);
            this.$enabledLocalizationSelect = $(this.options.enabledLocalizationSelect);
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [query, this.$websiteSelect.val()].join(this.options.delimiter);
        }
    });

    return Select2AutocompleteEnabledLocalizationComponent;
});
