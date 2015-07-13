define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var CalendarView = require('orocalendar/js/calendar-view');
    var EventCollection = require('orocalendar/js/calendar/event/collection');
    var ConnectionCollection = require('orocalendar/js/calendar/connection/collection');

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
        initialize: function(options) {
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
        prepareOptions: function() {
            var options = this.options;
            options.collection = this.eventCollection;
            options.scrollToCurrentTime = true;
            options.connectionsOptions.collection = this.connectionCollection;

            options.eventsOptions.header = {
                left: options.eventsOptions.leftHeader || '',
                center: options.eventsOptions.centerHeader || '',
                right: options.eventsOptions.rightHeader || ''
            };

            _.extend(options.eventsOptions, options.calendarOptions);

            delete options.calendarOptions;
            delete options.eventsOptions.centerHeader;
            delete options.eventsOptions.leftHeader;
            delete options.eventsOptions.rightHeader;
        },
        renderCalendar: function() {
            this.calendar = new CalendarView(this.options);
            this.calendar.render();
        }
    });

    return CalendarComponent;
});
