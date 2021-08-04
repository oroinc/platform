define([
    'underscore',
    'oro/datagrid/cell/number-cell',
    'orodatagrid/js/datagrid/formatter/currency-formatter'
], function(_, NumberCell, CurrencyFormatter) {
    'use strict';

    /**
     * Currency column cell.
     *
     * @export  oro/datagrid/cell/currency-cell
     * @class   oro.datagrid.cell.CurrencyCell
     * @extends NumberCell
     */
    const CurrencyCell = NumberCell.extend({
        /** @property {orodatagrid.datagrid.formatter.CurrencyFormatter} */
        formatterPrototype: CurrencyFormatter,

        /** @property {String} */
        style: 'currency',

        /**
         * @inheritdoc
         */
        constructor: function CurrencyCell(options) {
            CurrencyCell.__super__.constructor.call(this, options);
        }
    });

    return CurrencyCell;
});
