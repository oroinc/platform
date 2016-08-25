define(['underscore', 'jquery', 'backgrid'
    ], function(_, $, Backgrid) {
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
            phoneNumber = $.trim(phoneNumber);
            var phones = phoneNumber.split(/,\s*/);
            return phones.map(function(number) {
                return '<a href="tel:' + _.escape(number) + '" class="nowrap">' + _.escape(number) + '</a>';
            }).join(', ');
        },

        /**
         * Generate 'a' element
         * @param {string|number} phoneNumber
         * @return {string}
         */
        generateLinkHTML: function(phoneNumber) {
            return this.formatWithNbsp(phoneNumber);
        }
    });

    return PhoneFormatter;
});
