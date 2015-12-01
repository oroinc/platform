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
        render: function() {
            var render = StringCell.__super__.render.apply(this, arguments);

            this.enterEditMode();

            return render;
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function() {
            if (this.column.get('editable')) {
                StringCell.__super__.enterEditMode.apply(this, arguments);
            }
        },

        /**
         * @inheritDoc
         */
        exitEditMode: function() {
            if (!this.column.get('editable')) {
                StringCell.__super__.exitEditMode.apply(this, arguments);
            }
        }
    });

    return StringCell;
});
