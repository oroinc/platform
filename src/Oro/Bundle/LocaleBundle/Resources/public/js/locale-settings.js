define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const settings = require('js/oro.locale_data');
    const moduleConfig = require('module-config').default(module.id);

    /**
     * Locale settings
     *
     * @export  orolocale/js/locale-settings
     * @class   oro.LocaleSettings
     */
    const localeSettings = {
        defaults: {
            locale: 'en_US',
            language: 'en',
            rtl_mode: false,
            country: 'US',
            currency: 'USD',
            timezone: 'UTC',
            timezone_offset: '+00:00'
        },
        settings: {
            locale: 'en_US',
            language: 'en',
            rtl_mode: false,
            country: 'US',
            currency: 'USD',
            currencyViewType: 'symbol',
            currencySymbolPrepend: true,
            datetime_separator: false,
            timezone: 'UTC',
            timezone_offset: '+00:00',
            format_address_by_address_country: true,
            do_not_lowercase_noun_locales: false,
            apiKey: null,
            unit: {
                temperature: 'fahrenheit',
                wind_speed: 'miles_per_hour'
            },
            locale_data: {
                US: {
                    phone_prefix: '1',
                    default_locale: 'en_US',
                    currency_code: 'USD'
                }
            },
            currency_data: {
                USD: {
                    symbol: '$'
                }
            },
            format: {
                datetime: {
                    moment: {
                        day: 'MM-DD',
                        date: 'YYYY-MM-DD',
                        time: 'HH:mms',
                        datetime: 'YYYY-MM-DD HH:mm',
                        backend: 'YYYY-MM-DD HH:mm:ssZZ'
                    }
                },
                address: {
                    US: '%name%\n%organization%\n%street%\n%CITY% %REGION% %COUNTRY% %postal_code%',
                    ltrParts: ['phone', 'postal_code', 'region_code']
                },
                name: {
                    en_US: '%prefix% %first_name% %middle_name% %last_name% %suffix%'
                },
                number: {
                    decimal: {
                        grouping_size: 3,
                        grouping_used: 1,
                        max_fraction_digits: 14,
                        min_fraction_digits: 0,
                        negative_prefix: '-',
                        negative_suffix: '',
                        positive_prefix: '',
                        positive_suffix: '',
                        currency_code: '',
                        padding_character: '*',
                        decimal_separator_symbol: '.',
                        grouping_separator_symbol: ',',
                        monetary_separator_symbol: '.',
                        monetary_grouping_separator_symbol: ',',
                        currency_symbol: '¤',
                        zero_digit_symbol: '0'
                    },
                    percent: {
                        grouping_size: 3,
                        grouping_used: 1,
                        max_fraction_digits: 14,
                        min_fraction_digits: 0,
                        negative_prefix: '-',
                        negative_suffix: '%',
                        positive_prefix: '',
                        positive_suffix: '%',
                        currency_code: '',
                        padding_character: '*',
                        decimal_separator_symbol: '.',
                        grouping_separator_symbol: ',',
                        monetary_separator_symbol: '.',
                        monetary_grouping_separator_symbol: ',',
                        currency_symbol: '¤',
                        zero_digit_symbol: '0'
                    },
                    currency: {
                        grouping_size: 3,
                        grouping_used: 1,
                        max_fraction_digits: 2,
                        min_fraction_digits: 2,
                        negative_prefix: '-¤',
                        negative_suffix: '',
                        positive_prefix: '¤',
                        positive_suffix: '',
                        currency_code: '',
                        padding_character: '*',
                        decimal_separator_symbol: '.',
                        grouping_separator_symbol: ',',
                        monetary_separator_symbol: '.',
                        monetary_grouping_separator_symbol: ',',
                        currency_symbol: '¤',
                        zero_digit_symbol: '0'
                    }
                }
            },
            calendar: {
                dow: {
                    'wide': {
                        1: 'Sunday',
                        2: 'Monday',
                        3: 'Tuesday',
                        4: 'Wednesday',
                        5: 'Thursday',
                        6: 'Friday',
                        7: 'Saturday'
                    },
                    'abbreviated': {1: 'Sun', 2: 'Mon', 3: 'Tue', 4: 'Wed', 5: 'Thu', 6: 'Fri', 7: 'Sat'},
                    'short': {1: 'Su', 2: 'Mo', 3: 'Tu', 4: 'We', 5: 'Th', 6: 'Fr', 7: 'Sa'},
                    'narrow': {1: 'S', 2: 'M', 3: 'T', 4: 'W', 5: 'T', 6: 'F', 7: 'S'}
                },
                months: {
                    wide: {
                        1: 'January',
                        2: 'February',
                        3: 'March',
                        4: 'April',
                        5: 'May',
                        6: 'June',
                        7: 'July',
                        8: 'August',
                        9: 'September',
                        10: 'October',
                        11: 'November',
                        12: 'December'
                    },
                    abbreviated: {
                        1: 'Jan',
                        2: 'Feb',
                        3: 'Mar',
                        4: 'Apr',
                        5: 'May',
                        6: 'Jun',
                        7: 'Jul',
                        8: 'Aug',
                        9: 'Sep',
                        10: 'Oct',
                        11: 'Nov',
                        12: 'Dec'
                    },
                    narrow: {
                        1: 'J', 2: 'F', 3: 'M', 4: 'A', 5: 'M', 6: 'J',
                        7: 'J', 8: 'A', 9: 'S', 10: 'O', 11: 'N', 12: 'D'
                    }
                },
                first_dow: 1
            }
        },

        _deepExtend: function(target, source) {
            for (const prop in source) {
                if (source.hasOwnProperty(prop)) {
                    if (_.isObject(target[prop])) {
                        target[prop] = this._deepExtend(target[prop], source[prop]);
                    } else {
                        target[prop] = source[prop];
                    }
                }
            }
            return target;
        },

        extendSettings: function(settings) {
            this.settings = this._deepExtend(this.settings, settings);
            this._timezone_shift = this.calculateTimeZoneShift(this.getTimeZoneOffset());
        },

        extendDefaults: function(defaults) {
            this.defaults = this._deepExtend(this.defaults, defaults);
            this._timezone_shift = this.calculateTimeZoneShift(this.getTimeZoneOffset());
        },

        getLocale: function() {
            return this.settings.locale;
        },

        getCountry: function() {
            return this.settings.country;
        },

        getCurrency: function() {
            return this.settings.currency;
        },

        getCurrencyMinFractionDigits: function() {
            const format = this.getNumberFormats('currency');

            return format['min_fraction_digits'];
        },

        getCurrencyMaxFractionDigits: function() {
            const format = this.getNumberFormats('currency');

            return format['max_fraction_digits'];
        },

        getCurrencySymbol: function(currencyCode) {
            if (!currencyCode) {
                currencyCode = this.settings.currency;
            }
            if (this.settings.currency_data.hasOwnProperty(currencyCode)) {
                return this.settings.currency_data[currencyCode].symbol;
            }

            return currencyCode;
        },

        getCurrencyViewType: function() {
            return this.settings.currencyViewType;
        },

        isCurrencySymbolPrepend: function() {
            return this.settings.currencySymbolPrepend;
        },

        /**
         * @return {string} name of system tynezone
         */
        getTimeZone: function() {
            return this.settings.timezone;
        },

        getTimeZoneOffset: function() {
            return this.settings.timezone_offset;
        },

        /**
         * Calculates minutes of time zone shift
         * analog of (new Date).getTimezoneOffset()
         *
         * @returns {number}
         */
        getTimeZoneShift: function() {
            return this._timezone_shift;
        },

        /**
         * Calculates timezone shift in minutes by given string
         *
         * @param tz {string} timezone specification, just like "+08:00"
         * @returns {number} shift in minutes
         */
        calculateTimeZoneShift: function(tz) {
            const matches = tz.match(/^(\+|\-)(\d{2}):?(\d{2})$/);
            const sign = Number(matches[1] + '1');
            const hours = Number(matches[2]);
            const minutes = Number(matches[3]);
            return sign * (hours * 60 + minutes);
        },

        getNameFormats: function() {
            return this.settings.format.name;
        },

        getAddressFormats: function() {
            return this.settings.format.address;
        },

        getAddressLTRParts: function() {
            return this.settings.format.address.ltrParts;
        },

        getNumberFormats: function(style) {
            return this.settings.format.number[style];
        },

        getCountryLocale: function(country) {
            return this.getLocaleData(country, 'default_locale') || this.settings.locale;
        },

        /**
         * Gets default vendor specific locale for date time of specific type
         *
         * @param {string} vendor Registered vendor name, for example - "moment" or "jquery_ui"
         * @param {string} type "date"|"datetime"|"time"
         * @param {string} defaultValue
         * @returns {string}
         */
        getVendorDateTimeFormat: function(vendor, type, defaultValue) {
            if (this.settings.format.datetime.hasOwnProperty(vendor)) {
                type = (type && this.settings.format.datetime[vendor].hasOwnProperty(type)) ? type : 'datetime';

                return this.settings.format.datetime[vendor][type];
            }
            return defaultValue;
        },

        /**
         * Return separator betwean date and time for current format
         *
         * @returns {string}
         */
        getDateTimeFormatSeparator: function() {
            if (!this.settings.datetime_separator) {
                this.settings.datetime_separator = this.getVendorDateTimeFormat('jquery_ui', 'datetime', '')
                    .replace(localeSettings.getVendorDateTimeFormat('jquery_ui', 'date'), '')
                    .replace(localeSettings.getVendorDateTimeFormat('jquery_ui', 'time'), '');
            }

            return this.settings.datetime_separator;
        },

        getLocaleData: function(country, dataType) {
            if (this.settings.locale_data.hasOwnProperty(country)) {
                return this.settings.locale_data[country][dataType];
            }
            return null;
        },

        isFormatAddressByAddressCountry: function() {
            return this.settings.format_address_by_address_country;
        },

        /**
         * Gets months names array or object.
         *
         * If object then value of key '1' is January, if array first element is January
         *
         * @param {String} [width] "wide" - default |"abbreviated"|"narrow"
         * @param {Boolean} [asArray]
         * @returns {*}
         */
        getCalendarMonthNames: function(width, asArray) {
            width = (width && this.settings.calendar.months.hasOwnProperty(width)) ? width : 'wide';
            let result = this.settings.calendar.months[width];
            if (asArray) {
                result = _.map(result, function(v) {
                    return v;
                });
            }
            return result;
        },

        /**
         * Gets week day names array or object.
         *
         * If object then value of key '1' is Sunday, if array first element is Sunday
         *
         * @param {string} [width] "wide" - default |"abbreviated"|"short"|"narrow"
         * @param {boolean} [asArray] Default false
         * @returns {Object}|{Array}
         */
        getCalendarDayOfWeekNames: function(width, asArray) {
            width = (width && this.settings.calendar.dow.hasOwnProperty(width)) ? width : 'wide';
            const result = this.settings.calendar.dow[width];
            return asArray ? _.values(result) : _.clone(result);
        },

        /**
         * Gets week day names array where names are sorted by locale
         *
         * @param {string} [width] "wide" - default |"abbreviated"|"short"|"narrow"
         * @returns {Array}
         */
        getSortedDayOfWeekNames: function(width) {
            let dowNames = this.getCalendarDayOfWeekNames(width, true);
            const splitPoint = this.getCalendarFirstDayOfWeek() - 1;
            if (splitPoint > 0 && splitPoint < dowNames.length) {
                dowNames = dowNames.slice(splitPoint).concat(dowNames.slice(0, splitPoint));
            }
            return dowNames;
        },

        /**
         * Gets first day of week starting from 1.
         *
         * @returns {int}
         */
        getCalendarFirstDayOfWeek: function() {
            return this.settings.calendar.first_dow;
        },

        /**
         * Get array of possible locales - first locale is the best, last is the worst
         *
         * @param {string} locale
         * @returns {Array}
         */
        getLocaleFallback: function(locale) {
            const locales = [locale, this.settings.locale, this.defaults.locale];

            const getLocaleLang = function(locale) {
                return locale ? locale.split('_')[0] : locale;
            };

            const possibleLocales = [];
            for (let i = 0; i < locales.length; i++) {
                if (locales[i]) {
                    possibleLocales.push(locales[i]);
                    possibleLocales.push(getLocaleLang(locales[i]));
                }
            }

            return possibleLocales;
        },

        isNotLowercaseNounLocales: function() {
            return this.settings.do_not_lowercase_noun_locales;
        }
    };

    localeSettings.extendSettings(settings);
    localeSettings.extendDefaults(moduleConfig.defaults);
    localeSettings.extendSettings(moduleConfig.settings);

    return localeSettings;
});
