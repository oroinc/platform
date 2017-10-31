define(function(require) {
    'use strict';

    var moment = require('moment');
    var localeSettings = require('./locale-settings');
    var locale = localeSettings.getLocale();
    require('moment-timezone');

    var localeConfig = {
        months: localeSettings.getCalendarMonthNames('wide', true),
        monthsShort: localeSettings.getCalendarMonthNames('abbreviated', true),
        monthsParseExact: true,
        weekdays: localeSettings.getCalendarDayOfWeekNames('wide', true),
        weekdaysShort: localeSettings.getCalendarDayOfWeekNames('abbreviated', true),
        weekdaysMin: localeSettings.getCalendarDayOfWeekNames('short', true),
        weekdaysParseExact: true,
        week: {
            dow: localeSettings.getCalendarFirstDayOfWeek() - 1
        }
    };

    // check first if locale exists, then add or update existing
    if (locale === moment.locale(locale)) {
        moment.updateLocale(locale, localeConfig);
    } else {
        moment.locale(locale, localeConfig);
    }

    moment.defaultZone = moment.tz.zone(localeSettings.getTimeZone());

    return moment;
});
