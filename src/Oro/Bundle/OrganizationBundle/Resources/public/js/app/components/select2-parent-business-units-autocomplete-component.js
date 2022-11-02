define(function(require) {
    'use strict';

    const Select2TreeAutocompleteComponent = require('oro/select2-tree-autocomplete-component');
    const _ = require('underscore');

    const Select2ParentBusinessUnitsAutocompleteComponent = Select2TreeAutocompleteComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function Select2ParentBusinessUnitsAutocompleteComponent(options) {
            Select2ParentBusinessUnitsAutocompleteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            let params = {
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
            const queryParts = [
                query,
                configs.entity_id
            ];
            return queryParts.join(';');
        }
    });
    return Select2ParentBusinessUnitsAutocompleteComponent;
});
