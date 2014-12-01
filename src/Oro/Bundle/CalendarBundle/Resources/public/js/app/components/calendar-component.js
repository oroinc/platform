/*jslint nomen: true*/
/*global define*/
define(['jquery', 'underscore', 'oroui/js/app/components/base/component',
    'orocalendar/js/calendar', 'orocalendar/js/calendar/event/collection',
    'orocalendar/js/calendar/connection/collection', 'orolocale/js/locale-settings', 'oroui/js/mediator', 'orotranslation/js/translator'
    ], function ($, _, BaseComponent,
                 Calendar, EventCollection,
                 ConnectionCollection, localeSettings, mediator, __) {
    'use strict';

    /**
     * Creates calendar
     */
    var CalendarComponent = BaseComponent.extend({

        /**
         * @type {orocalendar.js.calendar}
         */
        calendar: null,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = options;
            if (!this.options.el) {
                this.options.el = this.options._sourceElement;
            }
            this.prepareOptions();
            this.renderCalendar();
        },
        prepareOptions: function () {
            var options = this.options;
            // prepare data for collections
            options.collection = new EventCollection(JSON.parse(options.eventsItemsJson));
            options.connectionsOptions.collection = new ConnectionCollection(JSON.parse(options.connectionsItemsJson));
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

            delete options.eventsItemsJson;
            delete options.connectionsItemsJson;
            delete options.calendarOptions;
            delete options.date;
            delete options.eventsOptions.centerHeader;
            delete options.eventsOptions.leftHeader;
            delete options.eventsOptions.rightHeader;
        },
        renderCalendar: function () {
            this.calendar = new Calendar(this.options);
            this.calendar.render();
            this.calendar.$el.data('calendar', this.calendar);
        }
    });

    return CalendarComponent;
});
