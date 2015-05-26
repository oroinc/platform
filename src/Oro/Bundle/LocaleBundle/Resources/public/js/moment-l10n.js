/*global define*/
define(function (require) {
    'use strict';

    var moment = require('moment'),
        __ = require('orotranslation/js/translator'),
        localeSettings = require('./locale-settings'),
        locale = localeSettings.getLocale();
    require('moment-timezone');

    moment.locale(locale, {
        months : localeSettings.getCalendarMonthNames('wide', true),
        monthsShort : localeSettings.getCalendarMonthNames('abbreviated', true),
        weekdays : localeSettings.getCalendarDayOfWeekNames('wide', true),
        weekdaysShort : localeSettings.getCalendarDayOfWeekNames('abbreviated', true),
        weekdaysMin : localeSettings.getCalendarDayOfWeekNames('short', true),
        week : {
            dow : localeSettings.getCalendarFirstDayOfWeek() - 1
        },
        meridiem : function (hours, minutes, isLower) {
            if (hours > 11) {
                return isLower ? __('pm') : __('PM');
            } else {
                return isLower ? __('am') : __('AM');
            }
        }
    });

    return moment;
});
