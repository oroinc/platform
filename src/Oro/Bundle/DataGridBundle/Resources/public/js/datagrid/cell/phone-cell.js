/*jslint nomen:true*/
/*global define*/
define([
    'backgrid',
    'orodatagrid/js/datagrid/formatter/phone-formatter'
], function (Backgrid, PhoneFormatter) {
    'use strict';

    var PhoneCell;

    /**
     * Phone cell
     *
     * @export  oro/datagrid/cell/phone-cell
     * @class   oro.datagrid.cell.PhoneCell
     * @extends Backgrid.Cell
     */
    PhoneCell = Backgrid.Cell.extend({
        /** @property */
        className: "phone-cell",

        /** @property */
        tagName: "td",

        /** @property */
        events: {
            "click": "stopPropagation"
        },

        /**
         @property {(Backgrid.PhoneFormatter|Object|string)}
         */
        formatter: new PhoneFormatter(),


        /**
         * @override
         * @inheritDoc
         * @return {oro.datagrid.cell.PhoneCell}
         */
        render: function() {
            var phoneNumber = this.model.get(this.column.get("name"));
            var formattedValue = this.formatter.fromRaw(phoneNumber, this.model);

            this.$el.empty();
            this.$el.html(formattedValue);

            return this;
        },

        /**
         * If don't stop propagation click will select row
         */
        stopPropagation: function (e) {
            e.stopPropagation();
        }
    });

    return PhoneCell;
});
