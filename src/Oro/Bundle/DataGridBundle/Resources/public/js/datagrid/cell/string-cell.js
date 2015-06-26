/*jslint nomen:true*/
/*global define*/
define([
    'backgrid',
    'orodatagrid/js/datagrid/formatter/cell-formatter'
], function(Backgrid, CellFormatter) {
    'use strict';

    var StringCell;

    /**
     * String column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/string-cell
     * @class   oro.datagrid.cell.StringCell
     * @extends Backgrid.StringCell
     */
    StringCell = Backgrid.StringCell.extend({
        /**
         @property {(Backgrid.CellFormatter|Object|string)}
         */
        formatter: new CellFormatter(),

        /**
         * @inheritDoc
         */
        enterEditMode: function(e) {
            if (this.column.get("editable")) {
                e.stopPropagation();
            }
            return StringCell.__super__.enterEditMode.apply(this, arguments);
        }
    });

    return StringCell;
});
