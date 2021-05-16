define(function(require) {
    'use strict';

    const _ = require('underscore');
    const AbstractFormatter = require('orofilter/js/formatter/abstract-formatter');
    const localeSettings = require('orolocale/js/locale-settings');
    const formatter = require('orolocale/js/formatter/number');

    /**
     * A floating point number formatter. Doesn't understand notation at the moment.
     *
     * @export  orofilter/js/formatter/number-formatter
     * @class   orofilter.formatter.NumberFormatter
     * @extends orofilter.formatter.AbstractFormatter
     * @throws {RangeError} If decimals < 0 or > 20.
     */
    const NumberFormatter = function(options) {
        options = options ? _.clone(options) : {};
        _.extend(this, this.defaults, options);

        if (this.decimals !== null && (typeof this.decimals !== 'number' || isNaN(this.decimals))) {
            throw new TypeError('decimals must be a number');
        }

        if (this.decimals < 0 || this.decimals > 20) {
            throw new RangeError('decimals must be between 0 and 20');
        }

        const numberFormats = localeSettings.getNumberFormats('decimal');
        this.decimalSeparator = numberFormats.decimal_separator_symbol;
    };

    NumberFormatter.prototype = new AbstractFormatter();

    _.extend(NumberFormatter.prototype, {
        /**
         * @memberOf orofilter.formatter.NumberFormatter
         * @cfg {Object} options
         *
         * @cfg {number} [options.decimals=2] Number of decimals to display. Must be an integer.
         *
         * @cfg {string} [options.decimalSeparator='.'] The separator to use when
         * displaying decimals.
         *
         * @cfg {string} [options.orderSeparator=','] The separator to use to
         * separator thousands. May be an empty string.
         */
        defaults: {
            decimals: 2,
            decimalSeparator: '.',
            orderSeparator: ''
        },

        HUMANIZED_NUM_RE: /(\d)(?=(?:\d{3})+$)/g,

        EMPTY_DECIMAL: '00',

        /**
         * Takes a floating point number and convert it to a formatted string where
         * every thousand is separated by `orderSeparator`, with a `decimal` number of
         * decimals separated by `decimalSeparator`. The number returned is rounded
         * the usual way.
         *
         * @memberOf orofilter.formatter.NumberFormatter
         * @param {number} number
         * @return {string|number}
         */
        fromRaw: function(number) {
            if (isNaN(number) || number === null) {
                return '';
            }

            if (this.percent) {
                return formatter.formatPercent(number, {scale_percent_by_100: false});
            } else {
                if (number === '') {
                    return number;
                }

                const numberString = number.toString();

                const parts = numberString.split('.');
                const integerPart = parts[0];
                let decimalPart = !_.isEmpty(parts[1]) ? parts[1] : '';
                if (decimalPart.length < this.decimals) {
                    const fixedParts = Number(number).toFixed(this.decimals).split('.');
                    decimalPart = (this.decimalSeparator || '.') + fixedParts[1];
                } else {
                    decimalPart = decimalPart ? (this.decimalSeparator || '.') + decimalPart : '';
                }

                return integerPart.replace(this.HUMANIZED_NUM_RE, '$1' + this.orderSeparator) + decimalPart;
            }
        },

        /**
         * Takes a floating point number and convert it to a formatted string where
         * every thousand is separated by `orderSeparator`, with a `decimal` number of
         *
         * @memberOf orofilter.formatter.NumberFormatter
         * @param {string} formattedData
         * @return {number|NaN} NaN if the string cannot be converted to
         * a number.
         */
        toRaw: function(formattedData) {
            if (formattedData === null || /^\s+$/.test(formattedData) || formattedData === '') {
                return void 0;
            }
            let rawData = '';
            let i;

            if (this.percent && formattedData.indexOf('%') > -1) {
                formattedData = formattedData.replace(/%/g, '');
            }

            const thousands = formattedData.trim().split(this.orderSeparator);
            for (i = 0; i < thousands.length; i++) {
                rawData += thousands[i];
            }

            const decimalParts = rawData.split(this.decimalSeparator);
            rawData = '';
            for (i = 0; i < decimalParts.length; i++) {
                rawData = rawData + decimalParts[i] + '.';
            }

            if (rawData[rawData.length - 1] === '.') {
                rawData = rawData.slice(0, rawData.length - 1);
            }

            const maxFractionDigits = localeSettings.getNumberFormats('decimal').max_fraction_digits;
            if (!_.isEmpty(decimalParts[1]) && decimalParts[1].length > maxFractionDigits) {
                return rawData;
            } else {
                const result = rawData * 1;

                if (_.isNumber(result) && !_.isNaN(result)) {
                    return result;
                }
            }
        }
    });

    return NumberFormatter;
});
