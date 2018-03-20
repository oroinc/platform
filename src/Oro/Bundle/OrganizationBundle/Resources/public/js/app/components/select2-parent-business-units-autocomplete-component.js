define(function(require) {
    'use strict';

    var Select2ParentBusinessUnitsAutocompleteComponent;
    var Select2TreeAutocompleteComponent = require('oro/select2-tree-autocomplete-component');
    var _ = require('underscore');

    Select2ParentBusinessUnitsAutocompleteComponent = Select2TreeAutocompleteComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function Select2ParentBusinessUnitsAutocompleteComponent() {
            Select2ParentBusinessUnitsAutocompleteComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var params = {
                entity_id: options.configs.entity_id
            };

            params = _.extend(
                {},
                {params: params},
                options._sourceElement.data('select2_query_additional_params') || {}
            );

            options._sourceElement.data('select2_query_additional_params', params);
            Select2ParentBusinessUnitsAutocompleteComponent.__super__.initialize.call(this, options);
        },

        makeQuery: function(query, configs) {
            var queryParts = [
                query,
                configs.entity_id
            ];
            return queryParts.join(';');
        }
    });
    return Select2ParentBusinessUnitsAutocompleteComponent;
});
