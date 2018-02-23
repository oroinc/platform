define(function(require) {
    'use strict';

    var GridColumns;
    var Backgrid = require('backgrid');
    var CellEventList = require('./cell-event-list');

    GridColumns = Backgrid.Columns.extend({
        comparator: 'order',

        /**
         * @inheritDoc
         */
        constructor: function GridColumns() {
            GridColumns.__super__.constructor.apply(this, arguments);
        },

        getCellEventList: function() {
            if (!this.cellEventList) {
                this.cellEventList = new CellEventList(this);
            }
            return this.cellEventList;
        }
    });

    return GridColumns;
});
