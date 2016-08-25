define(['numeral', '../locale-settings', 'underscore'
    ], function(numeral, localeSettings, _) {
    'use strict';

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
                var originLanguage = numeral.language();
                numeral.language(localeSettings.getLocale());
                var result = numeral(value).format(createFormat(options));
                if (result === '0') {
                    result = options.zero_digit_symbol;
                }
                numeral.language(originLanguage);
                return result;
            },
            addPrefixSuffix: function(formattedNumber, options, originalNumber) {
                var prefix = '';
                var suffix = '';
                if (originalNumber >= 0) {
                    prefix = options.positive_prefix;
                    suffix = options.positive_suffix;
                } else if (originalNumber < 0)  {
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
                return formattedNumber.replace(
                    options.currency_symbol,
                    localeSettings.getCurrencySymbol(options.currency_code)
                );
            }
        };

        var doFormat = function(value, options, formattersChain) {
            var result = value;
            for (var i = 0; i < formattersChain.length; ++i) {
                var formatter = formattersChain[i];
                result = formatter.call(this, result, options, value);
            }
            return result;
        };

        return {
            formatDecimal: function(value) {
                var options = localeSettings.getNumberFormats('decimal');
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
            formatCurrency: function(value, currency) {
                var options = localeSettings.getNumberFormats('currency');
                if (!currency) {
                    currency = localeSettings.getCurrency();
                }
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
                result.push(Math.floor(value / 3600));    // hours
                result.push(Math.floor(value / 60) % 60); // minutes
                result.push(value % 60);                  // seconds
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
                result[1] = parseInt(result[1], 10) * 60;   // minutes
                result[2] = parseInt(result[2], 10);        // seconds
                result = _.reduce(result, function(res, item) {
                    return res + item;
                });
                return result;
            },
            unformat: function(value) {
                var result = String(value);
                var originLanguage = numeral.language();
                numeral.language(localeSettings.getLocale());
                result = numeral().unformat(result);
                numeral.language(originLanguage);

                return result;
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
