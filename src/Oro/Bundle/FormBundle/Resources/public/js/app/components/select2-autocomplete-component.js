define(function (require) {
    'use strict';
    var Select2AutocompleteComponent,
        Select2Component = require('./select2-component');
    Select2AutocompleteComponent = Select2Component.extend({
        processExtraConfig: function (select2Config, params) {
            Select2AutocompleteComponent.__super__.processExtraConfig(select2Config, params);
            select2Config.ajax = {
                url: params.url,
                data: function (query, page) {
                    return {
                        page: page,
                        per_page: params.perPage,
                        name: select2Config.autocomplete_alias,
                        query: query
                    };
                },
                results: function (data, page) {
                    return data;
                }
            };

            params.$el.on('select2-init', function(e) {
                $(e.target).on('change', function(e){
                    $(this).data('selected-data', e.added);
                });
            });

            return select2Config;
        }
    });
    return Select2AutocompleteComponent;
});
