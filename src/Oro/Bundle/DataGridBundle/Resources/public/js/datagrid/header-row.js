define([
    'backgrid',
    './row'
], function(Backgrid, Row) {
    'use strict';

    var HeaderRow;

    HeaderRow = Backgrid.HeaderRow.extend({
        initialize: function(options) {
            HeaderRow.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);
        },

        updateCellsOrder: Row.prototype.updateCellsOrder
    });

    return HeaderRow;
});
