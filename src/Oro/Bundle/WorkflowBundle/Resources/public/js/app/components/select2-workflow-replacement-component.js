define(function(require) {
    'use strict';

    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');
    var _ = require('underscore');

    return Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            Select2AutocompleteComponent.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [query, this.options.configs.entityId].join(this.options.delimiter);
        },

        /**
         * @inheritDoc
         */
        preConfig: function(config) {
            config = Select2AutocompleteComponent.__super__.preConfig.apply(this, arguments);
            config.id = function(item) {
                return item.name;
            };

            return config;
        }
    });
});
