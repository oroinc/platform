define(function(require) {
    'use strict';

    var Select2AutocompleteEntityParentComponent;
    var _ = require('underscore');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2AutocompleteEntityParentComponent = Select2AutocompleteComponent.extend({
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
            Select2AutocompleteEntityParentComponent.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        makeQuery: function(query) {
            return [query, this.options.configs.entityId].join(this.options.delimiter);
        }
    });

    return Select2AutocompleteEntityParentComponent;
});
