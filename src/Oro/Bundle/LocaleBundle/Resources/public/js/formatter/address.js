define(function(require) {
    'use strict';

    const _ = require('underscore');
    const localeSettings = require('../locale-settings');
    const nameFormatter = require('./name');

    /**
     * Address formatter
     *
     * @export  orolocale/js/formatter/address
     * @name    orolocale.formatter.address
     */
    return {
        /**
         * @property {Array}
         */
        LTRAddressParts: localeSettings.getAddressLTRParts(),

        /**
         * @property {Object}
         */
        formats: localeSettings.getAddressFormats(),

        /**
         * @param {Object} address
         * @param {string} country ISO2 code
         * @param {string} newLine
         * @param {boolean} formatHtml
         * @returns {string}
         */
        format: function(address, country, newLine, formatHtml) {
            if (!country) {
                if (localeSettings.isFormatAddressByAddressCountry()) {
                    country = address.country_iso2;
                } else {
                    country = localeSettings.getCountry();
                }
            }
            newLine = newLine || '<br/>';

            const format = this.getAddressFormat(country);
            let formatted = format.replace(/%(\w+)%/g, (pattern, key) => {
                const lowerCaseKey = key.toLowerCase();
                let value = '';
                if ('name' === lowerCaseKey) {
                    value = nameFormatter.format(address, localeSettings.getCountryLocale(country));
                } else if ('street' === lowerCaseKey) {
                    value = (address.street || '') + ' ' + (address.street2 || '');
                } else if ('street1' === lowerCaseKey) {
                    value = address.street;
                } else {
                    value = address[lowerCaseKey];
                }
                if (value && key !== lowerCaseKey) {
                    value = value.toLocaleUpperCase();
                }

                if (formatHtml) {
                    return this.doFormatWithHtml(lowerCaseKey, value);
                }

                return value || '';
            });

            let addressLines = formatted
                .split('\\n');
            addressLines = addressLines.filter(function(element) {
                return element.trim() !== '';
            });
            if (typeof newLine === 'function') {
                for (let i = 0; i < addressLines.length; i++) {
                    addressLines[i] = newLine(addressLines[i]);
                }
                formatted = addressLines.join('');
            } else {
                formatted = addressLines.join(newLine);
            }
            return formatted.replace(/ +/g, ' ');
        },

        /**
         * @param {string} country ISO2 code
         * @returns {*}
         */
        getAddressFormat: function(country) {
            if (!this.formats.hasOwnProperty(country)) {
                country = localeSettings.getCountry();
            }
            return this.formats[country];
        },

        /**
         * @param {string} type
         * @param {string} value
         * @return {string}
         */
        doFormatWithHtml(type, value) {
            if (!type || !value) {
                return '';
            }

            const typePart = type.replace(/_/g, '-');
            const className = `address-part-${typePart}`;

            if (this.LTRAddressParts.includes(type)) {
                return `<bdo class="${className}" data-part="${typePart}" dir="ltr">${_.escape(value)}</bdo>`;
            }

            return `<span class="${className}" data-part="${typePart}">${_.escape(value)}</span>`;
        }
    };
});
