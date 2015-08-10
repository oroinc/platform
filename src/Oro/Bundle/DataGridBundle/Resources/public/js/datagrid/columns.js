define(function(require) {
    'use strict';

    var GridColumns;
    var Backgrid = require('backgrid');

    GridColumns = Backgrid.Columns.extend({
        comparator: function(a, b) {
            if (a.get('manageable') === false || b.get('manageable') === false || a.get('order') === b.get('order')) {
                return 0;
            }
            return a.get('order') > b.get('order') ? 1 : -1;
        }
    });

    return GridColumns;
});
