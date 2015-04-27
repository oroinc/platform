/*jslint nomen:true, browser:true*/
/*global define, window*/
define([
    'jquery',
    'underscore',
    'backgrid',
    './header-cell/header-cell'
], function ($, _, Backgrid, HeaderCell) {
    'use strict';

    var HeaderRow;

    /**
     * Grid header row.
     *
     * @export  orodatagrid/js/datagrid/header-row
     * @class   orodatagrid.datagrid.HeaderRow
     * @extends Backgrid.HeaderRow
     */
    HeaderRow = Backgrid.HeaderRow.extend({
        /**
         * @inheritDoc
         */
        makeCell: function (column, options) {
            var headerCell = column.get('headerCell') || options.headerCell || HeaderCell;
            headerCell = new headerCell({
                column: column,
                collection: this.collection
            });
            if (column.has('align')) {
                headerCell.$el.addClass('align-right');
            }
            return headerCell;
        }
        
    });

    return HeaderRow;
});
