define([
    'backgrid',
    './row'
], function(Backgrid, Row) {
    'use strict';

    var HeaderRow;

    HeaderRow = Backgrid.HeaderRow.extend({
        themeOptions: {
            optionPrefix: 'headerRow',
            className: 'grid-header-row'
        },

        initialize: function(options) {
            HeaderRow.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);
        },

        makeCell: function(column, options) {
            var HeaderCell = column.get('headerCell') || options.headerCell || Backgrid.HeaderCell;
            var cellOptions = {
                column: column,
                collection: this.collection,
                themeOptions: {
                    className: 'grid-cell grid-header-cell'
                }
            };
            if (column.get('name')) {
                cellOptions.themeOptions.className += ' grid-header-cell-' + column.get('name');
            }
            this.columns.trigger('configureInitializeOptions', HeaderCell, cellOptions);
            return new HeaderCell(cellOptions);
        },

        updateCellsOrder: Row.prototype.updateCellsOrder
    });

    return HeaderRow;
});
