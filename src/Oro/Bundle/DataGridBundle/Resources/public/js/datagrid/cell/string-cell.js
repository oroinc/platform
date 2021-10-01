define(function(require) {
    'use strict';

    const Backgrid = require('backgrid');
    const CellFormatter = require('orodatagrid/js/datagrid/formatter/cell-formatter');

    /**
     * String column cell. Added missing behaviour.
     *
     * @export  oro/datagrid/cell/string-cell
     * @class   oro.datagrid.cell.StringCell
     * @extends Backgrid.StringCell
     */
    const StringCell = Backgrid.StringCell.extend({
        /**
         @property {(Backgrid.CellFormatter|Object|string)}
         */
        formatter: new CellFormatter(),

        /**
         * @inheritdoc
         */
        constructor: function StringCell(options) {
            StringCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const render = StringCell.__super__.render.call(this);

            this.enterEditMode();

            return render;
        },

        /**
         * @inheritdoc
         */
        enterEditMode: function() {
            if (this.isEditableColumn()) {
                StringCell.__super__.enterEditMode.call(this);
            }
        },

        /**
         * @inheritdoc
         */
        exitEditMode: function() {
            if (!this.isEditableColumn()) {
                StringCell.__super__.exitEditMode.call(this);
            }
        }
    });

    return StringCell;
});
