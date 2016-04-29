define([
    'underscore',
    'backgrid',
    './footer-cell',
    '../row'
], function(_, Backgrid, FooterCell, Row) {
    'use strict';

    var FooterRow;

    /**
     * FooterRow is a controller for a row of footer cells.
     *
     * @exports orodatagrid/js/datagrid/footer/footer-row
     * @class orodatagrid.datagrid.footer.FooterRow
     * @extends Backgrid.Row
     */
    FooterRow = Backgrid.Row.extend({
        /** @property */
        footerCell: FooterCell,

        requiredOptions: ['columns', 'collection', 'footerCell'],

        initialize: function(options) {
            this.options = options || {};
            FooterRow.__super__.initialize.apply(this, arguments);

            this.listenTo(this.columns, 'sort', this.updateCellsOrder);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            _.each(this.cells, function(cell) {
                cell.dispose();
            });
            delete this.cells;
            delete this.columns;
            FooterRow.__super__.dispose.call(this);
        },

        makeCell: function(column, options) {
            var FooterCell = column.get('footerCell') || options.footerCell || this.footerCell;
            var cellOptions = {
                column: column,
                collection: this.collection,
                rowName: this.options.rowName,
                themeOptions: {
                    className: 'grid-cell grid-footer-cell'
                }
            };
            if (column.get('name')) {
                cellOptions.themeOptions.className += ' grid-footer-cell-' + column.get('name');
            }
            this.columns.trigger('configureInitializeOptions', FooterCell, cellOptions);
            return new FooterCell(cellOptions);
        },

        updateCellsOrder: Row.prototype.updateCellsOrder
    });

    return FooterRow;
});
