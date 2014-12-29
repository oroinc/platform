/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'backgrid',
    './footer-cell'
], function (_, Backgrid, FooterCell) {
    "use strict";

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

        requiredOptions: ["columns", "collection", "footerCell"],

        initialize: function (options) {
            this.options = options || {};
            FooterRow.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            _.each(this.cells, function (cell) {
                cell.dispose();
            });
            delete this.cells;
            delete this.columns;
            FooterRow.__super__.dispose.call(this);
        },

        makeCell: function (column, options) {
            var FooterCell = column.get("footerCell") || options.footerCell || this.footerCell;
            return new FooterCell({
                column: column,
                collection: this.collection,
                rowName: this.options.rowName
            });
        }
    });

    return FooterRow;
});
