/*global define*/
define(['./string-cell', '../formatter/datetime-formatter'
    ], function (StringCell, DatagridDateTimeFormatter) {
    'use strict';

    /**
     * Datetime column cell
     *
     * @export  orodatagrid/js/datagrid/cell/datetime-cell
     * @class   orodatagrid.datagrid.cell.DateTimeCell
     * @extends orodatagrid.datagrid.cell.StringCell
     */
    return StringCell.extend({
        /**
         * @property {orodatagrid.datagrid.formatter.DateTimeFormatter}
         */
        formatterPrototype: DatagridDateTimeFormatter,

        /**
         * @property {string}
         */
        type: 'dateTime',

        /**
         * @property {string}
         */
        className: 'datetime-cell',

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            StringCell.prototype.initialize.apply(this, arguments);
            this.formatter = this.createFormatter();
        },

        /**
         * Creates number cell formatter
         *
         * @return {orodatagrid.datagrid.formatter.DateTimeFormatter}
         */
        createFormatter: function() {
            return new this.formatterPrototype({type: this.type});
        }
    });
});
