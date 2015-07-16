define([
    'underscore',
    'backgrid',
    'orodatagrid/js/datagrid/formatter/number-formatter'
], function(_, Backgrid, NumberFormatter) {
    'use strict';

    var NumberCell;

    /**
     * Number column cell.
     *
     * @export  oro/datagrid/cell/number-cell
     * @class   oro.datagrid.cell.NumberCell
     * @extends Backgrid.NumberCell
     */
    NumberCell = Backgrid.NumberCell.extend({
        /** @property {orodatagrid.datagrid.formatter.NumberFormatter} */
        formatterPrototype: NumberFormatter,

        /** @property {String} */
        style: 'decimal',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(this, options);
            NumberCell.__super__.initialize.apply(this, arguments);
            this.formatter = this.createFormatter();
        },

        /**
         * Creates number cell formatter
         *
         * @return {orodatagrid.datagrid.formatter.NumberFormatter}
         */
        createFormatter: function() {
            return new this.formatterPrototype({style: this.style});
        },

        /**
         * @inheritDoc
         */
        enterEditMode: function(e) {
            if (this.column.get('editable')) {
                e.stopPropagation();
            }
            return NumberCell.__super__.enterEditMode.apply(this, arguments);
        }
    });

    return NumberCell;
});
