/*jslint nomen:true*/
/*global define*/
define([
    'oro/datagrid/cell/html-cell',
    'orodatagrid/js/datagrid/formatter/phone-formatter'
], function (HtmlCell, PhoneFormatter) {
    'use strict';

    var PhoneCell;

    /**
     * Phone cell
     *
     * @export  oro/datagrid/cell/phone-cell
     * @class   oro.datagrid.cell.PhoneCell
     * @extends oro.datagrid.cell.HtmlCell
     */
    PhoneCell = HtmlCell.extend({
        /** @property */
        className: "phone-cell",

        /** @property */
        events: {
            "click": "stopPropagation"
        },

        /**
         @property {(Backgrid.PhoneFormatter|Object|string)}
         */
        formatter: new PhoneFormatter(),

        /**
         * If don't stop propagation click will select row
         */
        stopPropagation: function (e) {
            e.stopPropagation();
        }
    });

    return PhoneCell;
});
