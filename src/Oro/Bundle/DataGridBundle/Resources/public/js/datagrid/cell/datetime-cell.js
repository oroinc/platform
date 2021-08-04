define([
    './string-cell',
    'orodatagrid/js/datagrid/formatter/datetime-formatter'
], function(StringCell, DatagridDateTimeFormatter) {
    'use strict';

    /**
     * Datetime column cell
     *
     * @export  oro/datagrid/cell/datetime-cell
     * @class   oro.datagrid.cell.DateTimeCell
     * @extends oro.datagrid.cell.StringCell
     */
    const DateTimeCell = StringCell.extend({
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
         * @inheritdoc
         */
        constructor: function DateTimeCell(options) {
            DateTimeCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            DateTimeCell.__super__.initialize.call(this, options);
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

    return DateTimeCell;
});
