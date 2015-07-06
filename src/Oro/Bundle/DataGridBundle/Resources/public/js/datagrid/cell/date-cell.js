define([
    './datetime-cell'
], function(DateTimeCell) {
    'use strict';

    var DateCell;

    /**
     * Date column cell
     *
     * @export  oro/datagrid/cell/date-cell
     * @class   oro.datagrid.cell.DateCell
     * @extends oro.datagrid.cell.DateTimeCell
     */
    DateCell = DateTimeCell.extend({
        type: 'date',
        className: 'date-cell'
    });

    return DateCell;
});
