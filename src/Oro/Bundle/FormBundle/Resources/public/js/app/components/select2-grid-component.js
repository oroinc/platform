define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Select2Component = require('oro/select2-component');

    const Select2GridComponent = Select2Component.extend({
        /**
         * @inheritdoc
         */
        constructor: function Select2GridComponent(options) {
            Select2GridComponent.__super__.constructor.call(this, options);
        },

        preConfig: function(config) {
            Select2GridComponent.__super__.preConfig.call(this, config);
            const that = this;
            const grid = config.grid;
            const gridName = grid.name;
            _.extend(config.ajax, {
                data: function(query, page, searchById) {
                    const result = {};
                    let sortByKey;
                    if (searchById) {
                        result[gridName + '[_pager][_page]'] = 1;
                        result[gridName + '[_pager][_per_page]'] = 1;
                        result[gridName + '[_filter][id][type]'] = 3;
                        result[gridName + '[_filter][id][value]'] = query;
                    } else {
                        sortByKey = grid.sort_by || config.properties[0];
                        result[gridName + '[_pager][_page]'] = page;
                        result[gridName + '[_pager][_per_page]'] = that.perPage;
                        result[gridName + '[_sort_by][' + sortByKey + ']'] = grid.sort_order || 'ASC';
                        result[gridName + '[_filter][' + config.properties[0] + '][type]'] = 1;
                        result[gridName + '[_filter][' + config.properties[0] + '][value]'] = query;
                    }
                    return result;
                },
                results: function(data, page) {
                    return {
                        results: data.data,
                        more: page * that.perPage < data.options.totalRecords
                    };
                }
            });
            return config;
        }
    });
    return Select2GridComponent;
});
