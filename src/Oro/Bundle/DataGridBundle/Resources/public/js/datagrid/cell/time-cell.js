define([
    './string-cell'
], function(StringCell) {
    'use strict';

    var TimeCell;

    /**
     * Time column cell
     *
     * @export  oro/datagrid/cell/time-cell
     * @class   oro.datagrid.cell.TimeCell
     * @extends oro.datagrid.cell.StringCell
     */
    TimeCell = StringCell.extend({
        type: 'time',
        className: 'time-cell'
    });

    return TimeCell;
});
