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

            this._computeLongValueClassName();
            this.enterEditMode();

            return render;
        },

        /**
         * Add specific classes to cell element if in has a long value
         * @private
         */
        _computeLongValueClassName() {
            const cellName = this.column.get('name');
            const value = this.model.get(cellName);
            const threshold = this.column.get('long_value_threshold');

            if (value && threshold) {
                this.$el.toggleClass(`grid-body-cell-${cellName}-long-value`, value.length >= threshold);
            }
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
