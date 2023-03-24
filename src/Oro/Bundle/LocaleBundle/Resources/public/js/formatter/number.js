define(function(require) {
    'use strict';

    const _ = require('underscore');
    const numeral = require('numeral');
    const localeSettings = require('../locale-settings');

    /**
     * Number Formatter
     *
     * @export orolocale/js/formatter/number
     * @name   orolocale.formatter.number
     */
    const numberFormatter = function() {
        const createFormat = function(options) {
            let format = !options.grouping_used ? '0' : '0,0';

            if (options.max_fraction_digits > 0) {
                format += '.';
                for (let i = 0; i < options.max_fraction_digits; ++i) {
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

        const formatters = {
            numeralFormat: function(value, options) {
                const originLocale = numeral.locale();
                numeral.locale(localeSettings.getLocale());
                let result = numeral(value).format(createFormat(options));
                if (result === '0') {
                    result = options.zero_digit_symbol;
                }
                numeral.locale(originLocale);
                return result;
            },
            addPrefixSuffix: function(formattedNumber, options, originalNumber) {
                let prefix = '';
                let suffix = '';
                if (originalNumber >= 0) {
                    prefix = options.positive_prefix;
                    suffix = options.positive_suffix;
                } else if (originalNumber < 0) {
                    formattedNumber = formattedNumber.replace('-', '');
                    prefix = options.negative_prefix;
                    suffix = options.negative_suffix;
                }
                const result = prefix + formattedNumber + suffix;

                // Fixes the case when sometimes (depending on ICU version) prefix/suffix contain XXX instead
                // of currency symbol.
                return result.replace('XXX', options.currency_symbol);
            },
            replaceMonetarySeparator: function(formattedNumber, options) {
                const defaultGroupingSeparator = ',';
                const defaultDecimalSeparator = '.';
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
                let currencyLayout = localeSettings.getCurrencyViewType() === 'symbol'
                    ? localeSettings.getCurrencySymbol(options.currency_code)
                    : options.currency_code;

                const isPrepend = localeSettings.isCurrencySymbolPrepend();

                if (localeSettings.getCurrencyViewType() !== 'symbol' && isPrepend) {
                    currencyLayout += '\u00A0';
                }

                return formattedNumber.replace(options.currency_symbol, currencyLayout);
            },
            dynamicPrecision: function(formattedNumber, options, originalNumber) {
                const originFractionDigits = getFractionDigits(originalNumber, options.min_fraction_digits);
                const formattedFractionDigits = getFractionDigits(formattedNumber, options.min_fraction_digits);

                if (originFractionDigits > formattedFractionDigits) {
                    originalNumber = String(originalNumber).replace(/0+$/, '');
                    const originalSplittedValue = originalNumber.split('.');

                    const originStyle = options.style;
                    const originMinFractionDigits = options.min_fraction_digits;
                    options.style = 'decimal';
                    options.min_fraction_digits = 0;
                    const formattedIntegralPart = doFormat(
                        originalSplittedValue[0],
                        options,
                        [formatters.numeralFormat]
                    );
                    options.style = originStyle;
                    options.min_fraction_digits = originMinFractionDigits;

                    if (originalSplittedValue[1]) {
                        return formattedIntegralPart.concat(
                            options.decimal_separator_symbol,
                            originalSplittedValue[1]
                        );
                    }
                }

                return formattedNumber;
            }
        };

        const doFormat = function(value, options, formattersChain) {
            let result = value;
            if (formattersChain.length) {
                result = Number(result);
            }
            for (let i = 0; i < formattersChain.length; ++i) {
                const formatter = formattersChain[i];
                result = formatter.call(this, result, options, value);
            }
            return result;
        };

        const allowedCustomOptions = [
            'grouping_used',
            'min_fraction_digits',
            'max_fraction_digits',
            'scale_percent_by_100'
        ];

        const prepareCustomOptions = function(opts) {
            if (!_.isObject(opts)) {
                return {};
            }

            return _.pick(opts, allowedCustomOptions);
        };

        const getFractionDigits = function(value, defaultFractionDigits) {
            const numberValue = Number(value);
            const digits = !Number.isNaN(numberValue) && Number.isFinite(numberValue) && numberValue % 1 !== 0
                ? value.toString().split('.')[1].length || 0
                : 0;

            return digits > defaultFractionDigits ? digits : defaultFractionDigits;
        };

        return {
            formatDecimal: function(value, opts) {
                const customOptions = prepareCustomOptions(opts);
                const formatOptions = this.formatOptions || {};
                const decimalOptions = localeSettings.getNumberFormats('decimal');
                const options = _.extend({}, decimalOptions, formatOptions, customOptions);
                options.style = 'decimal';
                const formattersChain = [
                    formatters.numeralFormat,
                    formatters.dynamicPrecision,
                    formatters.addPrefixSuffix
                ];

                return doFormat(value, options, formattersChain);
            },
            formatMonetary: function(value, opts) {
                const customOptions = prepareCustomOptions(opts);
                const decimalOptions = localeSettings.getNumberFormats('decimal');
                const fractionDigitsOptions = _.pick(localeSettings.getNumberFormats('currency'),
                    ['max_fraction_digits', 'min_fraction_digits']);
                const options = _.extend({}, decimalOptions, fractionDigitsOptions, customOptions);
                options.style = 'decimal';
                const formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix
                ];
                return doFormat(value, options, formattersChain);
            },
            formatInteger: function(value) {
                const options = _.extend({}, localeSettings.getNumberFormats('decimal'));
                options.style = 'integer';
                options.max_fraction_digits = 0;
                options.min_fraction_digits = 0;
                const formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix
                ];
                return doFormat(value, options, formattersChain);
            },
            formatPercent: function(value, opts) {
                const customOptions = prepareCustomOptions(opts);
                const percentOptions = localeSettings.getNumberFormats('percent');
                const options = _.extend({}, percentOptions, customOptions);
                options.style = 'percent';
                const formattersChain = [
                    formatters.numeralFormat,
                    formatters.clearPercent
                ];
                const originScalePercentBy100 = numeral.options.scalePercentBy100;
                if (options.scale_percent_by_100 === false) {
                    numeral.options.scalePercentBy100 = false;
                    formattersChain.push(formatters.dynamicPrecision);
                } else {
                    options.max_fraction_digits = getFractionDigits(value, percentOptions.max_fraction_digits);
                }
                formattersChain.push(formatters.addPrefixSuffix);
                const result = doFormat(value, options, formattersChain);
                numeral.options.scalePercentBy100 = originScalePercentBy100;
                return result;
            },
            formatCurrency: function(value, currency, opts) {
                const customOptions = prepareCustomOptions(opts);
                const currencyOptions = localeSettings.getNumberFormats('currency');
                if (!currency) {
                    currency = localeSettings.getCurrency();
                }
                const options = _.extend({}, currencyOptions, customOptions);
                options.min_fraction_digits = getFractionDigits(
                    Number(value),
                    localeSettings.getCurrencyMinFractionDigits()
                );
                options.max_fraction_digits = options.min_fraction_digits;
                options.style = 'currency';
                options.currency_code = currency;
                const formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix,
                    formatters.replaceCurrency
                ];
                return doFormat(value, options, formattersChain);
            },
            formatCurrencyRounded: function(value, currency, opts) {
                const customOptions = prepareCustomOptions(opts);
                const currencyOptions = localeSettings.getNumberFormats('currency');
                if (!currency) {
                    currency = localeSettings.getCurrency();
                }
                const options = _.extend({}, currencyOptions, customOptions);
                options.style = 'currency';
                options.min_fraction_digits = 0;
                options.max_fraction_digits = 0;
                options.currency_code = currency;
                const formattersChain = [
                    formatters.numeralFormat,
                    formatters.addPrefixSuffix,
                    formatters.replaceCurrency
                ];
                return doFormat(Math.round(value), options, formattersChain);
            },
            /**
             * Takes number of seconds and converts it into time duration formatted string
             * (formats 21811 => "06:03:31")
             *
             * @param {number} value
             * @return {string}
             */
            formatDuration: function(value) {
                const result = [];
                result.push(Math.floor(value / 3600)); // hours
                result.push(Math.floor(value / 60) % 60); // minutes
                result.push(value % 60); // seconds
                for (let i = 0; i < result.length; i++) {
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
                let result = value.split(':');
                result[0] = parseInt(result[0], 10) * 3600; // hours
                result[1] = parseInt(result[1], 10) * 60; // minutes
                result[2] = parseInt(result[2], 10); // seconds
                result = _.reduce(result, function(res, item) {
                    return res + item;
                });
                return result;
            },
            unformat: function(value) {
                let result = String(value);
                const originLocale = numeral.locale();
                numeral.locale(localeSettings.getLocale());
                result = numeral(result).value();
                numeral.locale(originLocale);

                return result;
            },
            unformatStrict: function(value) {
                const numberFormats = localeSettings.getNumberFormats('decimal');
                const groupingSeparator = numberFormats.grouping_separator_symbol;
                const decimalSeparator = numberFormats.decimal_separator_symbol;
                const defaultDecimalSeparator = '.';
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
