define([
    'backgrid',
    './row'
], function(Backgrid, Row) {
    'use strict';

    var HeaderRow;

    HeaderRow = Backgrid.HeaderRow.extend({
        viewOptions: {
            childViews: []
        },

        initialize: function(options) {
            HeaderRow.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);

            this.viewOptions.childViews.push.apply(this.viewOptions.childViews, this.cells);
        },

        updateCellsOrder: Row.prototype.updateCellsOrder
    });

    return HeaderRow;
});
