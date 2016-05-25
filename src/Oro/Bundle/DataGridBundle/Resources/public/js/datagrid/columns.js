define(function(require) {
    'use strict';

    var GridColumns;
    var Backgrid = require('backgrid');
    var CellEventList = require('./cell-event-list');

    GridColumns = Backgrid.Columns.extend({
        comparator: 'order',
        getCellEventList: function() {
            if (!this.cellEventList) {
                this.cellEventList = new CellEventList(this);
            }
            return this.cellEventList;
        }
    });

    return GridColumns;
});
