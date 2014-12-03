/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';

    var _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        Calendar = require('orocalendar/js/calendar'),
        EventCollection = require('orocalendar/js/calendar/event/collection'),
        ConnectionCollection = require('orocalendar/js/calendar/connection/collection'),
        localeSettings = require('orolocale/js/locale-settings'),
        __ = require('orotranslation/js/translator');

    /**
     * Creates calendar
     */
    var CalendarComponent = BaseComponent.extend({

        /**
         * @type {orocalendar.js.calendar}
         */
        calendar: null,

        /**
         * @type {EventCollection}
         */
        eventCollection: null,

        /**
         * @type {ConnectionCollection}
         */
        connectionCollection: null,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options;
            if (!this.options.el) {
                this.options.el = this.options._sourceElement;
            }
            this.eventCollection = new EventCollection(JSON.parse(this.options.eventsItemsJson));
            this.connectionCollection = new ConnectionCollection(JSON.parse(this.options.connectionsItemsJson));
            delete this.options.eventsItemsJson;
            delete this.options.connectionsItemsJson;
            this.prepareOptions();
            this.renderCalendar();
        },
        prepareOptions: function () {
            var options = this.options;
            // prepare data for collections
            options.collection = this.eventCollection;
            options.connectionsOptions.collection = this.connectionCollection;
            options.eventsOptions.subordinate = true;
            options.eventsOptions.date = options.date;
            options.eventsOptions.header = {
                left: options.eventsOptions.leftHeader || '',
                center: options.eventsOptions.centerHeader || '',
                right: options.eventsOptions.rightHeader || '',
                ignoreTimezone: false,
                allDayDefault: false
            }
            if (!options.eventsOptions.defaultView) {
                options.eventsOptions.defaultView = 'month';
            }
            options.eventsOptions.allDayText = __('oro.calendar.control.all_day');
            options.eventsOptions.buttonText = {
                today: __('oro.calendar.control.today'),
                month: __('oro.calendar.control.month'),
                week: __('oro.calendar.control.week'),
                day: __('oro.calendar.control.day')
            };

            options.eventsOptions.firstDay = localeSettings.getCalendarFirstDayOfWeek() - 1;
            options.eventsOptions.monthNames = localeSettings.getCalendarMonthNames('wide', true);
            options.eventsOptions.monthNamesShort = localeSettings.getCalendarMonthNames('abbreviated', true);
            options.eventsOptions.dayNames = localeSettings.getCalendarDayOfWeekNames('wide', true);
            options.eventsOptions.dayNamesShort = localeSettings.getCalendarDayOfWeekNames('abbreviated', true);

            _.extend(options.eventsOptions, options.calendarOptions);

            var dateFormat = localeSettings.getVendorDateTimeFormat('fullcalendar', 'date', 'MMM d, yyyy');
            var timeFormat = localeSettings.getVendorDateTimeFormat('fullcalendar', 'time', 'h:mm TT');
            // prepare FullCalendar specific date/time formats
            var isDateFormatStartedWithDay = dateFormat.indexOf('d') === 0;
            var weekFormat = isDateFormatStartedWithDay
                ? 'd[ MMMM][ yyyy]{ \'&#8212;\' d MMMM yyyy}'
                : 'MMMM d[ yyyy]{ \'&#8212;\'[ MMMM] d yyyy}';

            options.eventsOptions.titleFormat = {
                month: 'MMMM yyyy',
                week: weekFormat,
                day: 'dddd, ' + dateFormat
            };
            options.eventsOptions.columnFormat = {
                month: 'ddd',
                week: 'ddd ' + dateFormat,
                day: 'dddd ' + dateFormat
            };
            options.eventsOptions.timeFormat = {
                '': timeFormat,
                agenda: timeFormat + '{ - ' + timeFormat + '}'
            };
            options.eventsOptions.axisFormat = timeFormat;

            delete options.calendarOptions;
            delete options.date;
            delete options.eventsOptions.centerHeader;
            delete options.eventsOptions.leftHeader;
            delete options.eventsOptions.rightHeader;
        },
        renderCalendar: function () {
            this.calendar = new Calendar(this.options);
            this.calendar.render();
        }
    });

    return CalendarComponent;
});
