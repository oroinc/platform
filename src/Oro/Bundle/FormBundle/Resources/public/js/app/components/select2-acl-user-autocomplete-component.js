define(function (require) {
    'use strict';
    var Select2AclUserAutocompleteComponent,
        Select2Component = require('./select2-component');
    Select2AclUserAutocompleteComponent = Select2Component.extend({
        processExtraConfig: function (select2Config, url, perPage, $el) {
            select2Config.ajax = {
                url: url,
                data: function (query, page) {
                    var queryParts = [query, select2Config.entity_name, select2Config.permission, select2Config.entity_id];
                    if (select2Config.excludeCurrent === true) {
                        queryParts.push(1);
                    }
                    return {
                        page: page,
                        per_page: perPage,
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
