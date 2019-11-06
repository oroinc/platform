define(['../locale-settings'
], function(localeSettings) {
    'use strict';

    /**
     * Name formatter
     *
     * @export  orolocale/js/formatter/name
     * @name    orolocale.formatter.name
     */
    return {
        /**
         * @property {Object}
         */
        formats: localeSettings.getNameFormats(),

        /**
         * @property {Object}
         */
        formatCache: {},

        /**
         *
         * @param {Object} person
         * @param {string} locale
         * @returns {string}
         */
        format: function(person, locale) {
            if (!locale) {
                locale = localeSettings.getLocale();
            }

            const format = this.getNameFormat(locale);
            const formatted = format.replace(/%(\w+)%/g, function(pattern, key) {
                const lowerCaseKey = key.toLowerCase();
                let value = '';
                if (person.hasOwnProperty(lowerCaseKey)) {
                    value = person[lowerCaseKey];
                    if (key !== lowerCaseKey) {
                        value = value.toLocaleUpperCase();
                    }
                }
                return value || '';
            });

            return formatted
                .replace(/ +/g, ' ')
                .replace(/^\s+|\s+$/g, '');
        },

        /**
         * @param {string} locale
         * @returns {string}
         */
        getNameFormat: function(locale) {
            if (!this.formatCache.hasOwnProperty(locale)) {
                const localeFallback = localeSettings.getLocaleFallback(locale);

                let format = null;
                for (let i = 0; i < localeFallback.length; i++) {
                    if (this.formats.hasOwnProperty(localeFallback[i])) {
                        format = this.formats[localeFallback[i]];
                        break;
                    }
                }

                if (!format) {
                    throw new Error('Can\'t find name format for locale ' + locale);
                }

                this.formatCache[locale] = format;
            }

            return this.formatCache[locale];
        }
    };
});
