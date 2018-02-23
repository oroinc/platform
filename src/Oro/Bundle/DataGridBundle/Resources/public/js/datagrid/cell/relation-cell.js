define([
    'backgrid',
    'orodatagrid/js/datagrid/formatter/cell-formatter'
], function(Backgrid, CellFormatter) {
    'use strict';

    var RelationCell;

    /**
     * String column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/string-cell
     * @class   oro.datagrid.cell.StringCell
     * @extends Backgrid.StringCell
     */
    RelationCell = Backgrid.StringCell.extend({
        /**
         @property {(Backgrid.CellFormatter|Object|string)}
         */
        formatter: new CellFormatter(),

        /**
         * @inheritDoc
         */
        constructor: function RelationCell() {
            RelationCell.__super__.constructor.apply(this, arguments);
        }
    });

    return RelationCell;
});
