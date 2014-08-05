/*global define*/
define([
    './datetime-cell'
], function (DateTimeCell) {
    'use strict';

    var DateCell;

    /**
     * Date column cell
     *
     * @export  orodatagrid/js/datagrid/cell/date-cell
     * @class   orodatagrid.datagrid.cell.DateCell
     * @extends orodatagrid.datagrid.cell.DateTimeCell
     */
    DateCell = DateTimeCell.extend({
        type: 'date',
        className: 'date-cell'
    });

    return DateCell;
});
