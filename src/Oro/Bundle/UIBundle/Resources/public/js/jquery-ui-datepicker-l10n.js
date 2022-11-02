define(function(require) {
    'use strict';

    const $ = require('jquery');
    const moment = require('moment');
    const __ = require('orotranslation/js/translator');
    const localeSettings = require('orolocale/js/locale-settings');
    const locale = localeSettings.getLocale();
    require('jquery-ui/widgets/datepicker');

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
        weekDay: 'dddd Do',
        weekHeader: __('oro.ui.datepicker.wk'), // Column header for week of the year
        // See format options on parseDate
        dateFormat: localeSettings.getVendorDateTimeFormat('jquery_ui', 'date', 'mm/dd/yy'),
        firstDay: localeSettings.getCalendarFirstDayOfWeek() - 1, // The first day of the week, Sun = 0, Mon = 1, ...
        // isRTL: false, // True if right-to-left language, false if left-to-right
        // showMonthAfterYear: false, // True if the year select precedes month, false for month then year
        // yearSuffix: "" // Additional text to append to the year in the month headers
        gotoCurrent: true, // True if today link goes back to current selection instead
        applyTodayDateSelection: true, // Select the date on Today button click
        blurInputOnTodaySelection: true // Blur input when select today action
    };
    $.datepicker.setDefaults($.datepicker.regional[locale]);

    (function() {
        const _gotoToday = $.datepicker.constructor.prototype._gotoToday;
        const _updateDatepicker = $.datepicker.constructor.prototype._updateDatepicker;

        /**
         * Select today Date takes in account system timezone
         * @inheritdoc
         */
        $.datepicker.constructor.prototype._gotoToday = function(id) {
            const inst = this._getInst($(id)[0]);
            const now = moment.tz(localeSettings.getTimeZone());

            inst.currentDay = now.date();
            inst.currentMonth = now.month();
            inst.currentYear = now.year();
            _gotoToday.call(this, id);

            if (this._get(inst, 'applyTodayDateSelection')) {
                // select current day and close dropdown
                this._selectDate(id);
                this._get(inst, 'blurInputOnTodaySelection') && inst.input.blur();
            }
        };

        /**
         * Today Date highlight takes in account system timezone
         * @inheritdoc
         */
        $.datepicker.constructor.prototype._updateDatepicker = function(inst) {
            const today = moment.tz(localeSettings.getTimeZone());

            _updateDatepicker.call(this, inst);

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
