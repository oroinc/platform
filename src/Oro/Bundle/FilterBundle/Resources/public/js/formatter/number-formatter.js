define(function(require) {
    'use strict';

    var _ = require('underscore');
    var AbstractFormatter = require('./abstract-formatter');
    var formatter = require('orolocale/js/formatter/number');

    /**
     * A floating point number formatter. Doesn't understand notation at the moment.
     *
     * @export  orofilter/js/formatter/number-formatter
     * @class   orofilter.formatter.NumberFormatter
     * @extends orofilter.formatter.AbstractFormatter
     * @throws {RangeError} If decimals < 0 or > 20.
     */
    var NumberFormatter = function(options) {
        options = options ? _.clone(options) : {};
        _.extend(this, this.defaults, options);

        if (typeof this.decimals !== 'number' || isNaN(this.decimals)) {
            throw new TypeError('decimals must be a number');
        }

        if (this.decimals < 0 || this.decimals > 20) {
            throw new RangeError('decimals must be between 0 and 20');
        }
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
                return formatter.formatPercent(number / 100);
            } else {
                number = number.toFixed(this.decimals);

                var parts = number.split('.');
                var integerPart = parts[0];
                var isPercentValueTrim = parts[1] && parts[1] === this.EMPTY_DECIMAL && this.percent;
                var decimalPart = parts[1] && !isPercentValueTrim ? (this.decimalSeparator || '.') + parts[1] : '';

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
            var rawData = '';
            var i;

            if (this.percent && formattedData.indexOf('%') > -1) {
                formattedData = formattedData.replace(/%/g, '');
            }

            var thousands = formattedData.trim().split(this.orderSeparator);
            for (i = 0; i < thousands.length; i++) {
                rawData += thousands[i];
            }

            var decimalParts = rawData.split(this.decimalSeparator);
            rawData = '';
            for (i = 0; i < decimalParts.length; i++) {
                rawData = rawData + decimalParts[i] + '.';
            }

            if (rawData[rawData.length - 1] === '.') {
                rawData = rawData.slice(0, rawData.length - 1);
            }

            var result = rawData * 1;
            if (!this.percent) {
                result = result.toFixed(this.decimals) * 1;
            }

            if (_.isNumber(result) && !_.isNaN(result)) {
                return result;
            }
        }
    });

    return NumberFormatter;
});
