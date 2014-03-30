/*global define*/
define(['backgrid', '../formatter/cell-formatter'
    ], function (Backgrid, CellFormatter) {
    'use strict';

    /**
     * String column cell. Added missing behaviour.
     *
     * @export  orodatagrid/js/datagrid/cell/string-cell
     * @class   orodatagrid.datagrid.cell.StringCell
     * @extends Backgrid.StringCell
     */
    return Backgrid.StringCell.extend({
        /**
         @property {(Backgrid.CellFormatter|Object|string)}
         */
        formatter: new CellFormatter(),

        /**
         * @inheritDoc
         */
        enterEditMode: function (e) {
            if (this.column.get("editable")) {
                e.stopPropagation();
            }
            return Backgrid.StringCell.prototype.enterEditMode.apply(this, arguments);
        }
    });
});
