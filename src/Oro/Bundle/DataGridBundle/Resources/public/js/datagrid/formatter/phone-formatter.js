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
         * @param {string|number} phoneNumber
         * @return {string}
         */
        formatWithNbsp: function(phoneNumber) {
            var phones = phoneNumber.split(/,\s*/);
            return phones.map(function(number) {
                return '<span class="nowrap">' + _.escape(number) + '</span>';
            }).join(', ');
        },

        /**
         * Generate 'a' element
         * @param {string|number} phoneNumber
         * @return {string}
         */
        generateLinkHTML: function(phoneNumber) {
            return '<a href="tel:' + _.escape(phoneNumber) + '">' + this.formatWithNbsp(phoneNumber) + '</a>';
        }
    });

    return PhoneFormatter;
});
