define([
    'underscore',
    'oro/datagrid/cell/number-cell',
    'orodatagrid/js/datagrid/formatter/currency-formatter'
], function(_, NumberCell, CurrencyFormatter) {
    'use strict';

    var CurrencyCell;

    /**
     * Currency column cell.
     *
     * @export  oro/datagrid/cell/currency-cell
     * @class   oro.datagrid.cell.CurrencyCell
     * @extends NumberCell
     */
    CurrencyCell = NumberCell.extend({
        /** @property {orodatagrid.datagrid.formatter.CurrencyFormatter} */
        formatterPrototype: CurrencyFormatter,

        /** @property {String} */
        style: 'currency',

        /**
         * @inheritDoc
         */
        constructor: function CurrencyCell() {
            CurrencyCell.__super__.constructor.apply(this, arguments);
        }
    });

    return CurrencyCell;
});
