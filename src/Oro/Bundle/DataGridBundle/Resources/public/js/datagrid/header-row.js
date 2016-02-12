define([
    'underscore',
    'backgrid',
    './row',
    'orodatagrid/js/datagrid-view-options'
], function(_, Backgrid, Row, DataGridViewOptions) {
    'use strict';

    var HeaderRow;

    HeaderRow = Backgrid.HeaderRow.extend({
        viewOptions: {
            view: 'headerRow',
            className: 'grid-header-row'
        },

        initialize: function(options) {
            HeaderRow.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);
        },

        makeCell: function(column, options) {
            var HeaderCell = column.get('headerCell') || options.headerCell || Backgrid.HeaderCell;
            var viewOptions = _.extend({
                className: 'grid-header-cell'
            }, this.sourceViewOptions);
            HeaderCell = new (DataGridViewOptions.extend(HeaderCell, viewOptions))({
                column: column,
                collection: this.collection
            });
            return HeaderCell;
        },

        updateCellsOrder: Row.prototype.updateCellsOrder
    });

    return HeaderRow;
});
