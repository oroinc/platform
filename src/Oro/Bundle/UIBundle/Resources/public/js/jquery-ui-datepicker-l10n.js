define(function(require) {
    'use strict';

    var $ = require('jquery');
    var moment = require('moment');
    var __ = require('orotranslation/js/translator');
    var localeSettings = require('orolocale/js/locale-settings');
    var locale = localeSettings.getLocale();
    require('jquery-ui');

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
        // See format options on parseDate
        dateFormat: localeSettings.getVendorDateTimeFormat('jquery_ui', 'date', 'mm/dd/yy'),
        firstDay: localeSettings.getCalendarFirstDayOfWeek() - 1, // The first day of the week, Sun = 0, Mon = 1, ...
        //isRTL: false, // True if right-to-left language, false if left-to-right
        //showMonthAfterYear: false, // True if the year select precedes month, false for month then year
        //yearSuffix: "" // Additional text to append to the year in the month headers
        gotoCurrent: true, // True if today link goes back to current selection instead
        applyTodayDateSelection: true // Select the date on Today button click
    };
    $.datepicker.setDefaults($.datepicker.regional[locale]);

    (function() {
        var _gotoToday = $.datepicker._gotoToday;
        var _updateDatepicker = $.datepicker._updateDatepicker;

        /**
         * Select today Date takes in account system timezone
         * @inheritDoc
         */
        $.datepicker._gotoToday = function(id) {
            var inst = this._getInst($(id)[0]);
            var now = moment.tz(localeSettings.getTimeZone());

            inst.currentDay = now.date();
            inst.currentMonth = now.month();
            inst.currentYear = now.year();
            _gotoToday.apply(this, arguments);

            if (this._get(inst, 'applyTodayDateSelection')) {
                // select current day and close dropdown
                this._selectDate(id);
                inst.input.blur();
            }
        };

        /**
         * Today Date highlight takes in account system timezone
         * @inheritDoc
         */
        $.datepicker._updateDatepicker = function(inst) {
            var today = moment.tz(localeSettings.getTimeZone());

            _updateDatepicker.apply(this, arguments);

            // clear highlighted date
            inst.dpDiv
                .find('.ui-datepicker-today').removeClass('ui-datepicker-today')
                .find('a').removeClass('ui-state-highlight');

            if (inst.drawYear === today.year() && inst.drawMonth === today.month()) {
                // highlighted today date in system timezone
                inst.dpDiv
                    .find('td > a.ui-state-default').each(function() {
                    if (today.date().toString() === this.innerHTML) {
                        $(this).addClass('ui-state-highlight').parent().addClass('ui-datepicker-today');
                    }
                });
            }
        };
    })();
});
