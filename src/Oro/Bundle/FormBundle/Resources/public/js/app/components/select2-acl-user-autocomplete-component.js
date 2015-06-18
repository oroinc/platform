define(function (require) {
    'use strict';
    var Select2AclUserAutocompleteComponent,
        Select2Component = require('./select2-component');
    Select2AclUserAutocompleteComponent = Select2Component.extend({
        processExtraConfig: function (select2Config, params) {
            Select2AclUserAutocompleteComponent.__super__.processExtraConfig(select2Config, params);
            select2Config.ajax = {
                url: params.url,
                data: function (query, page) {
                    var queryParts = [
                        query,
                        select2Config.entity_name,
                        select2Config.permission,
                        select2Config.entity_id,
                        select2Config.excludeCurrent === true ? 1 : ''
                    ];
                    return {
                        page: page,
                        per_page: params.perPage,
                        name: select2Config.autocomplete_alias,
                        query: queryParts.join(';')
                    };
                },
                results: function (data, page) {
                    return data;
                }
            };
            return select2Config;
        }
    });
    return Select2AclUserAutocompleteComponent;
});
