define(function(require) {
    'use strict';

    const Select2Component = require('oro/select2-component');
    const _ = require('underscore');

    const Select2AclUserAutocompleteComponent = Select2Component.extend({
        /**
         * @inheritdoc
         */
        constructor: function Select2AclUserAutocompleteComponent(options) {
            Select2AclUserAutocompleteComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            let params = {
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
            const queryParts = [
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
