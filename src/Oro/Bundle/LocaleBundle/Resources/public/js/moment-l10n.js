define(function(require) {
    'use strict';

    var moment = require('moment');
    var __ = require('orotranslation/js/translator');
    var localeSettings = require('./locale-settings');
    var locale = localeSettings.getLocale();
    require('moment-timezone');

    moment.locale(locale, {
        months: localeSettings.getCalendarMonthNames('wide', true),
        monthsShort: localeSettings.getCalendarMonthNames('abbreviated', true),
        weekdays: localeSettings.getCalendarDayOfWeekNames('wide', true),
        weekdaysShort: localeSettings.getCalendarDayOfWeekNames('abbreviated', true),
        weekdaysMin: localeSettings.getCalendarDayOfWeekNames('short', true),
        week: {
            dow: localeSettings.getCalendarFirstDayOfWeek() - 1
        },
        meridiem: function(hours, minutes, isLower) {
            if (hours > 11) {
                return isLower ? __('pm') : __('PM');
            } else {
                return isLower ? __('am') : __('AM');
            }
        }
    });

    moment.defaultZone = moment.tz.zone(localeSettings.getTimeZone());

    return moment;
});
