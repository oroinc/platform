define(function(require) {
    'use strict';

    var numeral = require('numeral');
    var localeSettings = require('./locale-settings');
    var currencySettings = localeSettings.getNumberFormats('currency');

    numeral.locales[localeSettings.getLocale().toLowerCase()] = {
        delimiters: {
            thousands: currencySettings.grouping_separator_symbol,
            decimal: currencySettings.decimal_separator_symbol
        },
        abbreviations: {
            thousand: 'k',
            million: 'm',
            billion: 'b',
            trillion: 't'
        },
        ordinal: function(number) {
            var b = number % 10;
            return (~~(number % 100 / 10) === 1) ? 'th'
                : (b === 1) ? 'st'
                    : (b === 2) ? 'nd'
                        : (b === 3) ? 'rd' : 'th';
        },
        currency: {
            symbol: currencySettings.currency_symbol
        }
    };

    return numeral;
});
