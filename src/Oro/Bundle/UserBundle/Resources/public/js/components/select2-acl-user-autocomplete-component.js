define(function(require) {
    'use strict';

    var Select2AclUserAutocompleteComponent;
    var Select2Component = require('oro/select2-component');
    var _ = require('underscore');

    Select2AclUserAutocompleteComponent = Select2Component.extend({
        /**
         * @inheritDoc
         */
        constructor: function Select2AclUserAutocompleteComponent() {
            Select2AclUserAutocompleteComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var params = {
                permission: options.configs.permission,
                entity: options.configs.entity_name,
                entity_id: options.configs.entity_id
            };

            params = _.extend(
                {},
                {params: params},
                options._sourceElement.data('select2_query_additional_params') || {}
            );

            options._sourceElement.data('select2_query_additional_params', params);
            Select2AclUserAutocompleteComponent.__super__.initialize.call(this, options);
        },

        makeQuery: function(query, configs) {
            var queryParts = [
                query,
                configs.entity_name,
                configs.permission,
                configs.entity_id,
                configs.excludeCurrent === true ? 1 : ''
            ];
            return queryParts.join(';');
        }
    });
    return Select2AclUserAutocompleteComponent;
});
