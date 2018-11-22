define(function(require) {
    'use strict';

    var _ = require('underscore');
    var numeral = require('numeral');
    var localeSettings = require('../locale-settings');

    /**
     * Number Formatter
     *
     * @export orolocale/js/formatter/number
     * @name   orolocale.formatter.number
     */
    var numberFormatter = function() {
        var createFormat = function(options) {
            var format = !options.grouping_used ? '0' : '0,0';

            if (options.max_fraction_digits > 0) {
                format += '.';
                for (var i = 0; i < options.max_fraction_digits; ++i) {
                    if (options.min_fraction_digits === i) {
                        format += '[';
                    }
                    format += '0';
                }
                if (-1 !== format.indexOf('[')) {
                    format += ']';
                }
            }

            if (options.style === 'percent') {
                format += '%';
            }

            return format;
        };

        var formatters = {
            numeralFormat: function(value, options) {
                var originLocale = numeral.locale();
                numeral.locale(localeSettings.getLocale());
                var result = numeral(value).format(createFormat(options));
                if (result === '0') {
                    result = options.zero_digit_symbol;
                }
                numeral.locale(originLocale);
                return result;
            },
            addPrefixSuffix: function(formattedNumber, options, originalNumber) {
                var prefix = '';
                var suffix = '';
                if (originalNumber >= 0) {
                    prefix = options.positive_prefix;
                    suffix = options.positive_suffix;
                } else if (originalNumber < 0) {
                    formattedNumber = formattedNumber.replace('-', '');
                    prefix = options.negative_prefix;
                    suffix = options.negative_suffix;
                }
                return prefix + formattedNumber + suffix;
            },
            replaceMonetarySeparator: function(formattedNumber, options) {
                var defaultGroupingSeparator = ',';
                var defaultDecimalSeparator = '.';
                if (defaultGroupingSeparator !== options.monetary_grouping_separator_symbol) {
                    formattedNumber = formattedNumber
                        .replace(defaultGroupingSeparator, options.monetary_grouping_separator_symbol);
                }
                if (defaultDecimalSeparator !== options.monetary_separator_symbol) {
                    formattedNumber = formattedNumber
                        .replace(defaultDecimalSeparator, options.monetary_separator_symbol);
                }
                return formattedNumber;
            },
            clearPercent: function(formattedNumber) {
                return formattedNumber.replace('%', '');
            },
            replaceCurrency: function(formattedNumber, options) {
                var currencyLayout = localeSettings.getCurrencyViewType() === 'symbol'
                    ? localeSettings.getCurrencySymbol(options.currency_code)
                    : options.currency_code;

                var isPrepend = localeSettings.isCurrencySymbolPrepend();

                if (localeSettings.getCurrencyViewType() !== 'symbol' && isPrepend) {
                    currencyLayout += '\u00A0';
                }

                return formattedNumber.replace(options.currency_symbol, currencyLayout);
            }
        };

        var doFormat = function(value, options, formattersChain) {
            var result = value;
            if (formattersChain.length) {
                result = Number(result);
            }
            for (var i = 0; i < formattersChain.length; ++i) {
                var formatter = formattersChain[i];
                result = formatter.call(this, result, options, value);
            }
            return result;
        };

        var allowedCustomOptions = [
            'grouping_used',
            'min_fraction_digits',
            'max_fraction_digits'
        ];

        var prepareCustomOptions = function(opts) {
            if (!_.isObject(opts)) {
                return {};
            }

            return _.pick(opts, allowedCustomOptions);
        };

        return {
            formatDecimal: function(value, opts) {
                var customOptions = prepareCustomOptions(opts);
                var formatOptions = this.formatOptions || {};
                var decimalOptions = localeSettings.getNumberFormats('decimal');
                var options = _.extend({}, decimalOptions, formatOptions, customOptions);
                options.style = 'decimal';
                var formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix
                ];
                return doFormat(value, options, formattersChain);
            },
            formatMonetary: function(value, opts) {
                var customOptions = prepareCustomOptions(opts);
                var decimalOptions = localeSettings.getNumberFormats('decimal');
                var fractionDigitsOptions = _.pick(localeSettings.getNumberFormats('currency'),
                    ['max_fraction_digits', 'min_fraction_digits']);
                var options = _.extend({}, decimalOptions, fractionDigitsOptions, customOptions);
                options.style = 'decimal';
                var formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix
                ];
                return doFormat(value, options, formattersChain);
            },
            formatInteger: function(value) {
                var options = _.extend({}, localeSettings.getNumberFormats('decimal'));
                options.style = 'integer';
                options.max_fraction_digits = 0;
                options.min_fraction_digits = 0;
                var formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix
                ];
                return doFormat(value, options, formattersChain);
            },
            formatPercent: function(value) {
                var options = localeSettings.getNumberFormats('percent');
                options.style = 'percent';
                var formattersChain = [
                    formatters.numeralFormat,
                    formatters.clearPercent,
                    formatters.addPrefixSuffix
                ];
                return doFormat(value, options, formattersChain);
            },
            formatCurrency: function(value, currency, opts) {
                var customOptions = prepareCustomOptions(opts);
                var currencyOptions = localeSettings.getNumberFormats('currency');
                if (!currency) {
                    currency = localeSettings.getCurrency();
                }
                var options = _.extend({}, currencyOptions, customOptions);
                options.style = 'currency';
                options.currency_code = currency;
                var formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix,
                    formatters.replaceCurrency
                ];
                return doFormat(value, options, formattersChain);
            },
            /**
             * Takes number of seconds and converts it into time duration formatted string
             * (formats 21811 => "06:03:31")
             *
             * @param {number} value
             * @return {string}
             */
            formatDuration: function(value) {
                var result = [];
                result.push(Math.floor(value / 3600)); // hours
                result.push(Math.floor(value / 60) % 60); // minutes
                result.push(value % 60); // seconds
                for (var i = 0; i < result.length; i++) {
                    result[i] = String(result[i]);
                    if (result[i].length < 2) {
                        result[i] = '0' + result[i];
                    }
                }
                return result.join(':');
            },
            /**
             * Parses time duration formatted string and returns number of seconds
             * (converts "06:03:31" => 21811)
             *
             * @param {string} value
             * @return {number}
             */
            unformatDuration: function(value) {
                var result = value.split(':');
                result[0] = parseInt(result[0], 10) * 3600; // hours
                result[1] = parseInt(result[1], 10) * 60; // minutes
                result[2] = parseInt(result[2], 10); // seconds
                result = _.reduce(result, function(res, item) {
                    return res + item;
                });
                return result;
            },
            unformat: function(value) {
                var result = String(value);
                var originLocale = numeral.locale();
                numeral.locale(localeSettings.getLocale());
                result = numeral(result).value();
                numeral.locale(originLocale);

                return result;
            },
            unformatStrict: function(value) {
                var numberFormats = localeSettings.getNumberFormats('decimal');
                var groupingSeparator = numberFormats.grouping_separator_symbol;
                var decimalSeparator = numberFormats.decimal_separator_symbol;
                var defaultDecimalSeparator = '.';
                value = String(value);
                if (/^\s+$/.test(groupingSeparator)) {
                    // to avoid an error when grouping separator of current locale looks like space but has another code
                    // e.g. no-break space on french locale
                    value = value.replace(/\s/g, '');
                } else {
                    value = value.split(groupingSeparator).join('');
                }
                if (decimalSeparator !== defaultDecimalSeparator) {
                    if (value.indexOf(defaultDecimalSeparator) !== -1) {
                        // value should not contain default decimal separator if current locale has different one
                        return NaN;
                    }
                    value = value.replace(decimalSeparator, defaultDecimalSeparator);
                }
                return Number(value);
            }
        };
    };

    //    // decimal
    //    console.log('decimal', numberFormatter.formatDecimal(0));
    //    console.log('decimal', numberFormatter.formatDecimal(-123456789.123456));
    //    console.log('decimal', numberFormatter.formatDecimal(-123456789.123456));
    //
    //    // currency
    //    console.log('currency', numberFormatter.formatCurrency(123456789.123456));
    //    console.log('currency', numberFormatter.formatCurrency(-123456789.123456));
    //
    //    // integer
    //    console.log('currency', numberFormatter.formatCurrency(123456789.123456));
    //    console.log('integer', numberFormatter.formatInteger(-123456789.5));
    //
    //    // percent
    //    console.log('percent', numberFormatter.formatPercent(0.10));
    //    console.log('percent', numberFormatter.formatPercent(-0.10));
    //    console.log('percent', numberFormatter.formatPercent(1));

    return numberFormatter();
});
