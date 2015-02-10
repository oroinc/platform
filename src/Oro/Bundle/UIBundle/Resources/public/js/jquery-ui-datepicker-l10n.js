/* global define */
define(['jquery', 'orotranslation/js/translator',
        'orolocale/js/locale-settings','orolocale/js/formatter/datetime', 'jquery-ui'
    ],  function($, __, localeSettings, dateTimeFormatter) {
    'use strict';

    var locale = localeSettings.getLocale();

    $.datepicker.regional[locale] = {
        closeText: __('oro.ui.datepicker.close'), // Display text for close link
        prevText: __('oro.ui.datepicker.prev'), // Display text for previous month link
        nextText: __('oro.ui.datepicker.next'), // Display text for next month link
        currentText: __('oro.ui.datepicker.today'), // Display text for current month link
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
        weekHeader: __('oro.ui.datepicker.wk'), // Column header for week of the year
        dateFormat: localeSettings.getVendorDateTimeFormat('jquery_ui', 'date', 'mm/dd/yy'), // See format options on parseDate
        firstDay: localeSettings.getCalendarFirstDayOfWeek() - 1, // The first day of the week, Sun = 0, Mon = 1, ...
        //isRTL: false, // True if right-to-left language, false if left-to-right
        //showMonthAfterYear: false, // True if the year select precedes month, false for month then year
        //yearSuffix: "" // Additional text to append to the year in the month headers
        gotoCurrent: true, // True if today link goes back to current selection instead
        applyTodayDateSelection: true // Select the date on Today button click
    };
    $.datepicker.setDefaults($.datepicker.regional[locale]);

    $.datepicker._orig_base_gotoToday = $.datepicker._base_gotoToday ||$.datepicker._gotoToday;
    $.datepicker._base_gotoToday = $.datepicker._gotoToday = function (id) {
        var inst = this._getInst($(id)[0]),
            now = dateTimeFormatter.applyTimeZoneCorrection(new Date());
        inst.today = now;
        inst.currentDay = now.getDate();
        inst.currentMonth = now.getMonth();
        inst.currentYear = now.getFullYear();
        $.datepicker._orig_base_gotoToday.apply(this, arguments);

        if (this._get(inst, 'applyTodayDateSelection')) {
            // select current day and close dropdown
            this._selectDate(id);
            inst.input.blur();
        }
    };
});
