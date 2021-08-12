define([
    'oro/datagrid/cell/html-cell',
    'orodatagrid/js/datagrid/formatter/phone-formatter'
], function(HtmlCell, PhoneFormatter) {
    'use strict';

    /**
     * Phone cell
     *
     * @export  oro/datagrid/cell/phone-cell
     * @class   oro.datagrid.cell.PhoneCell
     * @extends oro.datagrid.cell.HtmlCell
     */
    const PhoneCell = HtmlCell.extend({
        /** @property */
        className: 'phone-cell',

        /** @property */
        events: {
            'click a': 'stopPropagation'
        },

        /**
         @property {(Backgrid.PhoneFormatter|Object|string)}
         */
        formatter: new PhoneFormatter(),

        /**
         * @inheritdoc
         */
        constructor: function PhoneCell(options) {
            PhoneCell.__super__.constructor.call(this, options);
        },

        /**
         * If don't stop propagation click will select row
         */
        stopPropagation: function(e) {
            e.stopPropagation();
        }
    });

    return PhoneCell;
});
