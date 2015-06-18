define(function (require) {
    'use strict';
    var Select2GridComponent,
        Select2Component = require('./select2-component');
    Select2GridComponent = Select2Component.extend({
        processExtraConfig: function (select2Config, params) {
            Select2GridComponent.__super__.processExtraConfig(select2Config, params);
            var grid = select2Config.grid,
                gridName = grid.name;
            select2Config.ajax = {
                url: params.url,
                data: function (query, page, searchById) {
                    var result = {},
                        sortByKey;
                    if (searchById) {
                        result[gridName + '[_pager][_page]'] = 1;
                        result[gridName + '[_pager][_per_page]'] = 1;
                        result[gridName + '[_filter][id][type]'] = 3;
                        result[gridName + '[_filter][id][value]'] = query;
                    } else {
                        sortByKey = grid.sort_by || select2Config.properties[0];
                        result[gridName + '[_pager][_page]'] = page;
                        result[gridName + '[_pager][_per_page]'] = perPage;
                        result[gridName + '[_sort_by][' + sortByKey + ']'] = grid.sort_order || 'ASC';
                        result[gridName + '[_filter][' + select2Config.properties[0] + '][type]'] = 1;
                        result[gridName + '[_filter][' + select2Config.properties[0] + '][value]'] = query;
                    }
                    return result;
                },
                results: function (data, page) {
                    return {
                        results: data.data,
                        more: page * params.perPage < data.options.totalRecords
                    };
                }
            };
            return select2Config;
        }
    });
    return Select2GridComponent;
});
