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
            return phoneNumber
                // disallow line breaks
                .replace(/\s/g, /* NON-BREAKING SPACE */'\u00A0')
                .replace(/([^0-9a-z\u00A0])/ig, /* zero width no-break space */'\uFEFF$1\uFEFF')
                // allow line break after closing bracket and comma
                .replace(/([),]|\+\d*)\uFEFF\uFEFF/ig, '$1')
                .replace(/([),]|\+\d*)\uFEFF/ig, '$1')
                .replace(/([),]|\+\d*)\u00A0/ig, '$1 ')
                .replace(/([),]|\+\d*)([^ ])/ig, /* ZERO WIDTH SPACE */'$1\u200B$2')
                // dissallow space between +\d+ and open bracket
                .replace(/(\+\d*)\s+\(/ig, '$1\u00A0(');
        },

        /**
         * Generate 'a' element
         * @param {string|number} phoneNumber
         * @return {string}
         */
        generateLinkHTML: function(phoneNumber) {
            return '<a href="tel:' + _.escape(phoneNumber) + '">' + _.escape(this.formatWithNbsp(phoneNumber)) + '</a>';
        }
    });

    return PhoneFormatter;
});
