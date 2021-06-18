define([
    './datetime-cell'
], function(DateTimeCell) {
    'use strict';

    /**
     * Date column cell
     *
     * @export  oro/datagrid/cell/date-cell
     * @class   oro.datagrid.cell.DateCell
     * @extends oro.datagrid.cell.DateTimeCell
     */
    const DateCell = DateTimeCell.extend({
        type: 'date',

        className: 'date-cell',

        /**
         * @inheritdoc
         */
        constructor: function DateCell(options) {
            DateCell.__super__.constructor.call(this, options);
        }
    });

    return DateCell;
});
