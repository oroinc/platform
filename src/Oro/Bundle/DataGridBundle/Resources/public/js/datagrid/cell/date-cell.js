/*global define*/
define(['./datetime-cell'
    ], function (DatagridDateTimeCell) {
    'use strict';

    /**
     * Date column cell
     *
     * @export  orodatagrid/js/datagrid/cell/date-cell
     * @class   orodatagrid.datagrid.cell.DateCell
     * @extends orodatagrid.datagrid.cell.DateTimeCell
     */
    return DatagridDateTimeCell.extend({type: 'date'});
});
