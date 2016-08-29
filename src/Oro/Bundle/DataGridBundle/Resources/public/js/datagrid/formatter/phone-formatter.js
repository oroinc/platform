define(['underscore', 'backgrid'
    ], function(_, Backgrid) {
    'use strict';

    /**
     * Phone number formatter
     *
     * @export  orodatagrid/js/datagrid/formatter/phone-formatter
     * @class   orodatagrid.datagrid.formatter.PhoneFormatter
     * @extends Backgrid.CellFormatter
     */
    var PhoneFormatter = function(options) {
        Backgrid.CellFormatter.call(this, options);
    };
    PhoneFormatter.prototype = Object.create(Backgrid.CellFormatter);

    _.extend(PhoneFormatter.prototype, {
        /**
         * @inheritDoc
         * @param {string|number} rawData
         * @return {string}
         */
        fromRaw: function(rawData) {
            if (rawData === null) {
                return '';
            }
            return this.generateLinkHTML(rawData);
        },

        /**
         * Generate 'a' element
         * @param {string|number} phoneNumber
         * @return {string}
         */
        generateLinkHTML: function(phoneNumber) {
            var number = phoneNumber.trim();
            return '<a href="tel:' + _.escape(number) + '" class="nowrap">' + _.escape(number) + '</a>';
        }
    });

    return PhoneFormatter;
});
