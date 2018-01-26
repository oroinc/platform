define(['../locale-settings', 'moment', 'orotranslation/js/translator'
], function(localeSettings, moment, __) {
    'use strict';

    var datetimeVendor = 'moment';

    /**
     * Datetime formatter
     *
     * @export  orolocale/js/formatter/datetime
     * @name    orolocale.formatter.datetime
     */
    return {
        /**
         * @property {Object}
         */
        frontendFormats: {
            day: localeSettings.getVendorDateTimeFormat(datetimeVendor, 'day'),
            date: localeSettings.getVendorDateTimeFormat(datetimeVendor, 'date'),
            time: localeSettings.getVendorDateTimeFormat(datetimeVendor, 'time'),
            datetime: localeSettings.getVendorDateTimeFormat(datetimeVendor, 'datetime')
        },

        /**
         * @property {Object}
         */
        backendFormats: {
            day: 'MM-DD',
            month: 'MM',
            date: 'YYYY-MM-DD',
            time: 'HH:mm:ss',
            datetime: 'YYYY-MM-DD[T]HH:mm:ssZZ',
            datetime_separator: 'T'
        },

        /**
         * @property {string}
         */
        timezoneOffset: localeSettings.getTimeZoneOffset(),

        /**
         * @property {string}
         */
        timezone: localeSettings.getTimeZone(),

        /**
         * @returns {string}
         */
        getDayFormat: function() {
            return this.frontendFormats.day;
        },

        /**
         * @returns {string}
         */
        getDateFormat: function() {
            return this.frontendFormats.date;
        },

        /**
         * @returns {string}
         */
        getTimeFormat: function() {
            return this.frontendFormats.time;
        },

        /**
         * @returns {string}
         */
        getDateTimeFormat: function() {
            return this.frontendFormats.datetime;
        },

        /**
         * @returns {string}
         */
        getDateTimeFormatNBSP: function() {
            if (!this.frontendFormats.datetimeNBSP) {
                this.frontendFormats.datetimeNBSP = this.prepareNbspFormat(this.frontendFormats.datetime);
            }
            return this.frontendFormats.datetimeNBSP;
        },

        /**
         * Replaces spaces to nbsp in format
         *
         * @param {string} format
         * @returns {string}
         */
        prepareNbspFormat: function(format) {
            format = format.replace(/\s+/g, '\u00a0');
            // format starts from time part
            if (/[AaHsSzZ]/.test(format[0])) {
                // first nbps before date part replace to usual space
                format = format.replace(/([^xXgGYWwEdDQM\u00a0])\s([^HsSAazZ]+)$/, '$1 $2');
            } else {
                // first nbps before time part replace to usual space
                format = format.replace(/([^HsSAazZ\u00a0])\s([^xXgGYWwEdDQM]+)$/, '$1 $2');
            }
            return format;
        },

        /**
         * Return separator between date and time for current format
         *
         * @returns {string}
         */
        getDateTimeFormatSeparator: function() {
            return localeSettings.getDateTimeFormatSeparator();
        },

        /**
         * @returns {string}
         */
        getBackendDayFormat: function() {
            return this.backendFormats.day;
        },

        /**
         * @returns {string}
         */
        getBackendMonthFormat: function() {
            return this.backendFormats.month;
        },

        /**
         * @returns {string}
         */
        getBackendDateFormat: function() {
            return this.backendFormats.date;
        },

        /**
         * @returns {string}
         */
        getBackendTimeFormat: function() {
            return this.backendFormats.time;
        },

        /**
         * @returns {string}
         */
        getBackendDateTimeFormat: function() {
            return this.backendFormats.datetime;
        },

        /**
         * Return separator between date and time for backend format
         *
         * @returns {string}
         */
        getBackendDateTimeFormatSeparator: function() {
            return this.backendFormats.datetime_separator;
        },

        /**
         * Matches any date value to custom format
         *
         * @param {string} value
         * @param {string|Array.<string>} format
         * @param {boolean=} strict by default its true
         * @returns {boolean}
         */
        isValueValid: function(value, format, strict) {
            return moment(value, format, strict !== false).isValid();
        },

        /**
         * Checks if passed date value matches frontend format
         *
         * @param {string} value
         * @param {boolean=} strict
         * @returns {boolean}
         */
        isDateValid: function(value, strict) {
            return this.isValueValid(value, this.getDateFormat(), strict);
        },

        /**
         * Checks if passed time value matches frontend format
         *
         * @param {string} value
         * @param {boolean=} strict
         * @returns {boolean}
         */
        isTimeValid: function(value, strict) {
            return this.isValueValid(value, this.getTimeFormat(), strict);
        },

        /**
         * Checks if passed date time value matches frontend format
         *
         * @param {string} value
         * @param {boolean=} strict
         * @returns {boolean}
         */
        isDateTimeValid: function(value, strict) {
            return this.isValueValid(value, this.getDateTimeFormat(), strict);
        },

        /**
         * Checks if passed date value matches backend format
         *
         * @param {string} value
         * @param {boolean=} strict
         * @returns {boolean}
         */
        isBackendDateValid: function(value, strict) {
            return this.isValueValid(value, this.getBackendDateFormat(), strict);
        },

        /**
         * Checks if passed time value matches backend format
         *
         * @param {string} value
         * @param {boolean=} strict
         * @returns {boolean}
         */
        isBackendTimeValid: function(value, strict) {
            return this.isValueValid(value, this.getBackendTimeFormat(), strict);
        },

        /**
         * Checks if passed date time value matches backend format
         *
         * @param {string} value
         * @param {boolean=} strict
         * @returns {boolean}
         */
        isBackendDateTimeValid: function(value, strict) {
            return this.isValueValid(value, this.getBackendDateTimeFormat(), strict);
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        formatDate: function(value) {
            return this.getMomentForBackendDate(value).format(this.getDateFormat());
        },

        /**
         * Get Date object based on formatted backend date string
         *
         * @param {string} value
         * @returns {Date}
         */
        unformatBackendDate: function(value) {
            return this.getMomentForBackendDate(value).toDate();
        },

        /**
         * Get moment object based on formatted backend date string
         *
         * @param {string} value
         * @returns {moment}
         */
        getMomentForBackendDate: function(value) {
            var momentDate = moment.utc(value);
            if (!momentDate.isValid()) {
                throw new Error('Invalid backend date ' + value);
            }
            return momentDate;
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        formatTime: function(value) {
            return this.getMomentForBackendTime(value).format(this.getTimeFormat());
        },

        /**
         * Get Date object based on formatted backend time string
         *
         * @param {string} value
         * @returns {Date}
         */
        unformatBackendTime: function(value) {
            return this.getMomentForBackendTime(value).toDate();
        },

        /**
         * Get moment object based on formatted backend date string
         *
         * @param {string} value
         * @returns {moment}
         */
        getMomentForBackendTime: function(value) {
            var momentTime = moment.utc(value, ['HH:mm:ss', 'HH:mm']);
            if (!momentTime.isValid()) {
                throw new Error('Invalid backend time ' + value);
            }
            return momentTime;
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        formatDateTime: function(value) {
            return this.getMomentForBackendDateTime(value).tz(this.timezone)
                .format(this.getDateTimeFormat());
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        formatDateTimeNBSP: function(value) {
            return this.getMomentForBackendDateTime(value).tz(this.timezone)
                .format(this.getDateTimeFormatNBSP());
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        formatSmartDateTime: function(value) {
            var dateMoment = this.getMomentForBackendDateTime(value).tz(this.timezone);
            var todayMoment = moment().tz(this.timezone);
            // full date with year
            var result = this.formatDate(value);

            if (result === todayMoment.format(this.getDateFormat())) {
                // same day, only show time
                result = dateMoment.format(this.getTimeFormat());
            } else if (result === todayMoment.clone().subtract(1, 'days').format(this.getDateFormat())) {
                // yesterday
                result = __('Yesterday');
            } else if (dateMoment.year() === todayMoment.year()) {
                // same year, return only day and month
                result = dateMoment.format(this.getDayFormat());
            }

            return result;
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        formatDayDateTime: function(value) {
            return this.getMomentForBackendDateTime(value).tz(this.timezone)
                .format(this.getDayFormat());
        },

        /**
         * Get Date object based on formatted backend date time string
         *
         * @param {string} value
         * @returns {Date}
         */
        unformatBackendDateTime: function(value) {
            return this.getMomentForBackendDateTime(value).toDate();
        },

        /**
         * Get moment object based on formatted backend date time string
         * (returns moment in UTC time zone)
         *
         * @param {string} value
         * @returns {moment} in UTC time zone
         */
        getMomentForBackendDateTime: function(value) {
            var momentDateTime = moment.utc(value);
            if (!momentDateTime.isValid()) {
                throw new Error('Invalid backend datetime ' + value);
            }
            return momentDateTime;
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        convertDateToBackendFormat: function(value) {
            return this.getMomentForFrontendDate(value).format(this.getBackendDateFormat());
        },

        /**
         * @param {string} value
         * @returns {string}
         */
        convertTimeToBackendFormat: function(value) {
            return this.getMomentForFrontendTime(value).format(this.getBackendTimeFormat());
        },

        /**
         * @param {string} value
         * @param {string=} timezone name of time zone
         * @returns {string}
         */
        convertDateTimeToBackendFormat: function(value, timezone) {
            return this.getMomentForFrontendDateTime(value, timezone).utc()
                .format(this.getBackendDateTimeFormat());
        },

        /**
         * Get moment object based on formatted frontend date string
         *
         * @param {string} value
         * @returns {moment}
         */
        getMomentForFrontendDate: function(value) {
            if (this.isDateObject(value)) {
                return this.formatDate(value);
            } else if (!this.isDateValid(value)) {
                throw new Error('Invalid frontend date ' + value);
            }

            return moment.utc(value, this.getDateFormat());
        },

        /**
         * Get Date object based on formatted frontend date string
         *
         * @param {string} value
         * @returns {Date}
         */
        unformatDate: function(value) {
            return this.getMomentForFrontendDate(value).toDate();
        },

        /**
         * Get moment object based on formatted frontend time string
         *
         * @param {string} value
         * @returns {moment}
         */
        getMomentForFrontendTime: function(value) {
            if (this.isDateObject(value)) {
                value = this.formatTime(value);
            } else if (!this.isTimeValid(value)) {
                throw new Error('Invalid frontend time ' + value);
            }

            return moment.utc(value, this.getTimeFormat());
        },

        /**
         * Get Date object based on formatted frontend time string
         *
         * @param {string} value
         * @returns {Date}
         */
        unformatTime: function(value) {
            return this.getMomentForFrontendTime(value).toDate();
        },

        /**
         * Get moment object based on formatted frontend date time string
         * (returns moment in custom time zone, by default it is system time zone)
         *
         * @param {string} value
         * @param {string=} timezone
         * @returns {moment} in custom time zone (by default it is system time zone)
         */
        getMomentForFrontendDateTime: function(value, timezone) {
            timezone = timezone || this.timezone;
            return moment(value, this.getDateTimeFormat()).tz(timezone, true);
        },

        /**
         * Get Date object based on formatted frontend date time string
         *
         * @param {string} value
         * @param {string=} timezone name of time zone
         * @returns {Date}
         */
        unformatDateTime: function(value, timezone) {
            return this.getMomentForFrontendDateTime(value, timezone).toDate();
        },

        /**
         * Check that obj is Date object
         *
         * @private
         * @param {string|Date} obj
         * @returns {boolean}
         */
        isDateObject: function(obj) {
            return Object.prototype.toString.call(obj) === '[object Date]';
        }
    };
});
