define(function (require) {
    'use strict';
    var Select2RelationComponent,
        Select2Component = require('./select2-component');
    Select2RelationComponent = Select2Component.extend({
        processExtraConfig: function (select2Config, params) {
            Select2RelationComponent.__super__.processExtraConfig(select2Config, params);
            select2Config.ajax = {
                url: params.url,
                data: function (query, page) {
                    return {
                        page: page,
                        per_page: params.perPage,
                        name: select2Config.autocomplete_alias,
                        query: [query, select2Config.target_entity, select2Config.target_field].join(',')
                    };
                },
                results: function (data, page) {
                    return data;
                }
            };
            return select2Config;
        }
    });
    return Select2RelationComponent;
});
