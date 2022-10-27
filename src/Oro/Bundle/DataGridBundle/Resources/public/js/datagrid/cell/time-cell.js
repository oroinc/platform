define([
    './string-cell'
], function(StringCell) {
    'use strict';

    /**
     * Time column cell
     *
     * @export  oro/datagrid/cell/time-cell
     * @class   oro.datagrid.cell.TimeCell
     * @extends oro.datagrid.cell.StringCell
     */
    const TimeCell = StringCell.extend({
        type: 'time',

        className: 'time-cell',

        /**
         * @inheritdoc
         */
        constructor: function TimeCell(options) {
            TimeCell.__super__.constructor.call(this, options);
        }
    });

    return TimeCell;
});
