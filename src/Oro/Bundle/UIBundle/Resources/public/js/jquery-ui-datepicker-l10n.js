/* global define */
define(['jquery', 'orotranslation/js/translator', 'orolocale/js/locale-settings', 'jquery-ui'],
function($, __, localeSettings) {
    'use strict';

    var locale = localeSettings.getLocale(),
        tz = localeSettings.getTimeZoneOffset(),
        offset = Number(tz.slice(0,1) + 1) + (Number(tz.slice(1,3)) * 60 + Number(tz.slice(4,6))) * 60000;

    $.datepicker.regional[locale] = {
        closeText: __("Done"), // Display text for close link
        prevText: __("Prev"), // Display text for previous month link
        nextText: __("Next"), // Display text for next month link
        currentText: __("Today"), // Display text for current month link
        // ["January","February","March","April","May","June", "July",
        // "August","September","October","November","December"]
        // Names of months for drop-down and formatting
        monthNames: localeSettings.getCalendarMonthNames('wide', true),
        // ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"] For formatting
        monthNamesShort: localeSettings.getCalendarMonthNames('abbreviated', true),
        // ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"] For formatting
        dayNames: localeSettings.getCalendarDayOfWeekNames('wide', true),
        // ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"] For formatting
        dayNamesShort: localeSettings.getCalendarDayOfWeekNames('abbreviated', true),
        // ["Su","Mo","Tu","We","Th","Fr","Sa"] Column headings for days starting at Sunday
        dayNamesMin: localeSettings.getCalendarDayOfWeekNames('narrow', true),
        weekHeader: __("Wk"), // Column header for week of the year
        dateFormat: localeSettings.getVendorDateTimeFormat('jquery_ui', 'date', 'mm/dd/yy'), // See format options on parseDate
        firstDay: localeSettings.getCalendarFirstDayOfWeek() - 1, // The first day of the week, Sun = 0, Mon = 1, ...
        //isRTL: false, // True if right-to-left language, false if left-to-right
        //showMonthAfterYear: false, // True if the year select precedes month, false for month then year
        //yearSuffix: "" // Additional text to append to the year in the month headers
        gotoCurrent: true
    };
    $.datepicker.setDefaults($.datepicker.regional[locale]);

    $.datepicker._orig_base_gotoToday = $.datepicker._base_gotoToday ||$.datepicker._gotoToday;
    $.datepicker._base_gotoToday = $.datepicker._gotoToday = function (id) {
        var inst = this._getInst($(id)[0]),
            local = new Date(),
            utc = local.getTime() + (local.getTimezoneOffset() * 60000),
            now = new Date(utc + offset);
        inst.today = now;
        inst.currentDay = now.getDate();
        inst.currentMonth = now.getMonth();
        inst.currentYear = now.getFullYear();
        $.datepicker._orig_base_gotoToday.apply(this, arguments);
    };
});
