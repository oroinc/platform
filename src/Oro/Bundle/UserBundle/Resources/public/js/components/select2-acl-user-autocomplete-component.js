define(function(require) {
    'use strict';
    var Select2AclUserAutocompleteComponent,
        Select2Component = require('oro/select2-component');
    Select2AclUserAutocompleteComponent = Select2Component.extend({
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
