define(['./columns'], function(GridColumns) {
    'use strict';

    return {
        createFilteredColumnCollection: function(columns) {
            var filteredColumns = new GridColumns(columns.where({renderable: true}));

            filteredColumns.listenTo(columns, 'change:renderable add remove reset', function() {
                filteredColumns.reset(columns.where({renderable: true}));
            });

            filteredColumns.listenTo(columns, 'sort', function() {
                filteredColumns.sort();
            });

            return filteredColumns;
        }
    };
});
