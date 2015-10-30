define(function(require) {
    'use strict';

    var GridColumns;
    var Backgrid = require('backgrid');

    GridColumns = Backgrid.Columns.extend({
        comparator: 'order'
    });

    return GridColumns;
});
