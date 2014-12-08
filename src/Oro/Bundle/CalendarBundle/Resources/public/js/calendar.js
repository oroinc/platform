/*jslint nomen:true*/
/*jshint devel:true*/
/*global define, console*/
define(function (require) {
    'use strict';

    var _ = require('underscore'),
        Backbone = require('backbone'),
        __ = require('orotranslation/js/translator'),
        messenger = require('oroui/js/messenger'),
        LoadingMask = require('oroui/js/loading-mask'),
        EventCollection = require('orocalendar/js/calendar/event/collection'),
        EventModel = require('orocalendar/js/calendar/event/model'),
        EventView = require('orocalendar/js/calendar/event/view'),
        ConnectionCollection = require('orocalendar/js/calendar/connection/collection'),
        ConnectionView = require('orocalendar/js/calendar/connection/view'),
        ColorManager = require('orocalendar/js/calendar/color-manager'),
        dateTimeFormatter = require('orolocale/js/formatter/datetime'),
        localeSettings = require('orolocale/js/locale-settings');
        require('jquery.fullcalendar');

    var $ = Backbone.$;

    /**
     * @export  orocalendar/js/calendar
     * @class   orocalendar.Сalendar
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        MOMENT_BACKEND_FORMAT: localeSettings.getVendorDateTimeFormat('moment', 'backend', 'YYYY-MM-DD HH:mm:ssZZ'),
        /** @property */
        eventsTemplate: _.template(
            '<div>' +
                '<div class="calendar-container">' +
                    '<div class="calendar"></div>' +
                    '<div class="loading-mask"></div>' +
                '</div>' +
            '</div>'
        ),

        /** @property {Object} */
        selectors: {
            calendar:           '.calendar',
            loadingMask:        '.loading-mask',
            loadingMaskContent: '.loading-content'
        },

        /** @property {Object} */
        options: {
            timezone: localeSettings.getTimeZoneShift(),
            eventsOptions: {
                defaultView: 'month',
                allDayText: __('oro.calendar.control.all_day'),
                buttonText: {
                    today: __('oro.calendar.control.today'),
                    month: __('oro.calendar.control.month'),
                    week: __('oro.calendar.control.week'),
                    day: __('oro.calendar.control.day')
                },
                editable: true,
                removable: true,
                collection: null,
                itemViewTemplateSelector: null,
                itemFormTemplateSelector: null,
                itemFormDeleteButtonSelector: null,
                calendar: null,
                subordinate: true,
                header: {
                    ignoreTimezone: false,
                    allDayDefault: false
                },
                firstDay: localeSettings.getCalendarFirstDayOfWeek() - 1,
                monthNames: localeSettings.getCalendarMonthNames('wide', true),
                monthNamesShort: localeSettings.getCalendarMonthNames('abbreviated', true),
                dayNames: localeSettings.getCalendarDayOfWeekNames('wide', true),
                dayNamesShort: localeSettings.getCalendarDayOfWeekNames('abbreviated', true)
            },
            connectionsOptions: {
                collection: null,
                containerTemplateSelector: null
            },
            colorManagerOptions: {
                colors: null
            }
        },

        /**
         * this property is used to prevent loading of events from a server when the calendar object is created
         * @property {bool}
         */
        enableEventLoading: false,
        fullCalendar: null,
        eventView: null,
        loadingMask: null,
        colorManager: null,

        /**
         * This property can be used to prevent unnecessary reloading of calendar events.
         * key = calendarUid
         * @property
         */
        eventsLoaded: {},

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            // init event collection
            this.collection = this.collection || new EventCollection();
            this.collection.setCalendar(this.options.calendar);
            this.collection.subordinate = this.options.eventsOptions.subordinate;

            // set options for new events
            this.options.newEventEditable = this.options.eventsOptions.editable;
            this.options.newEventRemovable = this.options.eventsOptions.removable;

            // subscribe to event collection events
            this.listenTo(this.collection, 'add', this.onEventAdded);
            this.listenTo(this.collection, 'change', this.onEventChanged);
            this.listenTo(this.collection, 'destroy', this.onEventDeleted);
            this.colorManager = new ColorManager(this.options.colorManagerOptions);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.getCalendarElement().data('fullCalendar')) {
                this.getCalendarElement().fullCalendar('destroy');
            }
            if (this.connectionsView) {
                this.connectionsView.dispose.call(this);
            }
            Backbone.View.prototype.dispose.call(this);
        },

        getEventView: function (eventModel) {
            if (!this.eventView) {
                var connectionModel = this.getConnectionCollection().findWhere(
                        {calendarUid: eventModel.get('calendarUid')}
                    ),
                    options = connectionModel.get('options') || {};
                // create a view for event details
                this.eventView = new EventView(_.extend({}, options, {
                    model: eventModel,
                    viewTemplateSelector: this.options.eventsOptions.itemViewTemplateSelector,
                    formTemplateSelector: this.options.eventsOptions.itemFormTemplateSelector,
                    colorManager: this.colorManager
                }));
                // subscribe to event view collection events
                this.listenTo(this.eventView, 'addEvent', this.handleEventViewAdd);
                this.listenTo(this.eventView, 'remove', this.handleEventViewRemove);
            }
            return this.eventView;
        },

        handleEventViewRemove: function () {
            this.eventView = null;
        },

        /**
         * Init and get a loading mask control
         *
         * @returns {Element}
         */
        getLoadingMask: function () {
            if (!this.loadingMask) {
                this.loadingMask = new LoadingMask();
                this.$el.find(this.selectors.loadingMask).append(this.loadingMask.render().$el);
            }
            return this.loadingMask;
        },

        getCollection: function () {
            return this.collection;
        },

        getConnectionCollection: function () {
            return this.options.connectionsOptions.collection;
        },

        getCalendarElement: function () {
            if (!this.fullCalendar) {
                this.fullCalendar = this.$el.find(this.selectors.calendar);
            }
            return this.fullCalendar;
        },

        handleEventViewAdd: function (eventModel) {
            this.collection.add(eventModel);
        },

        visibleDefaultCalendar: function (eventModel) {

        },

        addEventToCalendar: function (eventModel) {
            var fcEvent = eventModel.toJSON();

            this.prepareViewModel(fcEvent);
            this.applyTzCorrection(1, fcEvent);
            this.getCalendarElement().fullCalendar('renderEvent', fcEvent);
        },

        getCalendarEvents: function (calendarUid) {
            return this.getCalendarElement().fullCalendar('clientEvents', function (fcEvent) {
                return fcEvent.calendarUid === calendarUid;
            });
        },

        onEventAdded: function (eventModel) {
            var connectionModel = this.getConnectionCollection().findWhere({calendarUid: eventModel.get('calendarUid')});

            this.addEventToCalendar(eventModel);

            // make sure that a calendar is visible when a new event is added to it
            if (!connectionModel.get('visible')) {
                this.connectionsView.showCalendar(connectionModel);
            }
        },

        onEventChanged: function (eventModel) {
            var fcEvent = this.getCalendarElement().fullCalendar('clientEvents', eventModel.id)[0];
            // copy all fields, except id, from event to fcEvent
            fcEvent = _.extend(fcEvent, eventModel.toJSON());
            this.prepareViewModel(fcEvent);
            this.applyTzCorrection(1, fcEvent);
            // fullcalendar doesn't remember new duration during updateEvent
            // so need to store it
            fcEvent.duration = moment.duration(fcEvent.end.diff(fcEvent.start));
            // due to fullcalendar bug cannot update single event
            // please check that after updating fullcalendar
            // this.getCalendarElement().fullCalendar('updateEvent', fcEvent);
            this.getCalendarElement().fullCalendar('rerenderEvents');
        },

        onEventDeleted: function (eventModel) {
            this.getCalendarElement().fullCalendar('removeEvents', eventModel.id);
        },

        onConnectionAdded: function () {
            this.getCalendarElement().fullCalendar('refetchEvents');
        },

        onConnectionChanged: function (connectionModel) {
            if (connectionModel.reloadEventsRequest !== null) {
                if (connectionModel.reloadEventsRequest === true) {
                    this.getCalendarElement().fullCalendar('refetchEvents');
                }
                connectionModel.reloadEventsRequest = null;
                return;
            }

            var changes = connectionModel.changedAttributes(),
                calendarUid = connectionModel.get('calendarUid');
            if (_.has(changes, 'visible')) {
                if (changes.visible) {
                    if (this.eventsLoaded[calendarUid]) {
                        _.each(this.collection.where({calendarUid: calendarUid}), function (eventModel) {
                            this.addEventToCalendar(eventModel);
                        }, this);
                    } else {
                        this.getCalendarElement().fullCalendar('refetchEvents');
                    }
                } else {
                    this.getCalendarElement().fullCalendar('removeEvents', function (fcEvent) {
                        return fcEvent.calendarUid === calendarUid;
                    });
                }
            }
            if (_.has(changes, 'backgroundColor') && connectionModel.get('visible')) {
                _.each(this.getCalendarEvents(calendarUid), function (fcEvent) {
                    this.prepareViewModel(fcEvent);
                }, this);
                this.getCalendarElement().fullCalendar('rerenderEvents');
            }
        },

        onConnectionDeleted: function () {
            this.getCalendarElement().fullCalendar('refetchEvents');
        },

        select: function (start, end) {
            if (!this.eventView) {
                try {

                    var attrs = {
                            start: start,
                            end: end
                        },
                        eventModel;
                    this.applyTzCorrection(-1, attrs);

                    attrs.start = attrs.start.format(this.MOMENT_BACKEND_FORMAT);
                    attrs.end = attrs.end.format(this.MOMENT_BACKEND_FORMAT);

                    _.extend(
                        attrs,
                        {
                            calendarAlias: 'user',
                            calendar: this.options.calendar,
                            editable: this.options.newEventEditable,
                            removable: this.options.newEventRemovable
                        }
                    );
                    eventModel = new EventModel(attrs);
                    this.getEventView(eventModel).render();
                } catch (err) {
                    this.showMiscError(err);
                }
            }
        },

        eventClick: function (fcEvent) {
            if (!this.eventView) {
                try {
                    var eventModel = this.collection.get(fcEvent.id);
                    this.getEventView(eventModel).render();
                } catch (err) {
                    this.showMiscError(err);
                }
            }
        },

        eventResize: function (fcEvent, newDuration, undo) {
            fcEvent.end = fcEvent.start.clone().add(newDuration);
            this.saveFcEvent(fcEvent);
        },

        eventDrop: function (fcEvent, dateChangeDiff, undo) {
            fcEvent.end = (!fcEvent.duration) ? fcEvent.end.clone() : fcEvent.start.clone().add(fcEvent.duration);
            this.saveFcEvent(fcEvent);
        },

        saveFcEvent: function (fcEvent) {
            this.showSavingMask();
            try {
                var attrs = {
                        start: fcEvent.start.clone(),
                        end: fcEvent.end.clone()
                    },
                    model = this.collection.get(fcEvent.id);

                this.applyTzCorrection(-1, attrs);

                attrs.start = attrs.start.format(this.MOMENT_BACKEND_FORMAT);
                attrs.end = attrs.end.format(this.MOMENT_BACKEND_FORMAT);

                model.save(
                    attrs,
                    {
                        success: _.bind(this._hideMask, this),
                        error: _.bind(function (model, response) {
                            this.showSaveEventError(response.responseJSON || {});
                        }, this)
                    }
                );
            } catch (err) {
                this.showLoadEventsError(err);
            }
        },

        loadEvents: function (start, end, timezone, callback) {
            var onEventsLoad = _.bind(function () {
                var fcEvents = this.collection.toJSON();
                _.each(fcEvents, function (fcEvent) {
                    this.prepareViewModel(fcEvent);
                    this.applyTzCorrection(1, fcEvent);
                }, this);
                this.eventsLoaded = {};
                this.options.connectionsOptions.collection.each(function (connectionModel) {
                    if (connectionModel.get('visible')) {
                        this.eventsLoaded[connectionModel.get('calendarUid')] = true;
                    }
                }, this);
                this._hideMask();
                callback(fcEvents);
            }, this);

            try {
                this.collection.setRange(
                    start.format(this.MOMENT_BACKEND_FORMAT),
                    end.format(this.MOMENT_BACKEND_FORMAT)
                );
                if (this.enableEventLoading) {
                    // load events from a server
                    this.collection.fetch({
                        reset: true,
                        success: onEventsLoad,
                        error: _.bind(function (collection, response) {
                            callback({});
                            this.showLoadEventsError(response.responseJSON || {});
                        }, this)
                    });
                } else {
                    // use already loaded events
                    onEventsLoad();
                }
            } catch (err) {
                callback({});
                this.showLoadEventsError(err);
            }
        },

        /**
         * Prepares event entry for rendering in calendar plugin
         *
         * @param {Object} fcEvent
         * @param {boolean=} applyTZCorrection by default applies time zone correction
         */
        prepareViewModel: function (fcEvent) {
            // set an event text and background colors the same as the owning calendar
            var colors = this.colorManager.getCalendarColors(fcEvent.calendarUid);
            fcEvent.textColor = colors.color;
            fcEvent.color = colors.backgroundColor;
        },

        applyTzCorrection: function (sign, event) {
            if (!moment.isMoment(event.start)) {
                event.start = $.fullCalendar.moment(event.start);
            }
            if (!moment.isMoment(event.end)) {
                event.end = $.fullCalendar.moment(event.end);
            }
            event.start.zone(0).add(this.options.timezone * sign, 'm');
            event.end.zone(0).add(this.options.timezone * sign, 'm');
            return event;
        },

        showSavingMask: function () {
            this._showMask(__('Saving...'));
        },

        showLoadingMask: function () {
            this._showMask(__('Loading...'));
        },

        _showMask: function (message) {
            if (this.enableEventLoading) {
                var loadingMaskInstance = this.getLoadingMask();
                loadingMaskInstance.$el
                    .find(this.selectors.loadingMaskContent)
                    .text(message);
                loadingMaskInstance.show();
            }
        },

        _hideMask: function () {
            if (this.loadingMask) {
                this.loadingMask.hide();
            }
        },

        showLoadEventsError: function (err) {
            this._showError(__('Sorry, calendar events were not loaded correctly'), err);
        },

        showSaveEventError: function (err) {
            this._showError(__('Sorry, calendar event was not saved correctly'), err);
        },

        showMiscError: function (err) {
            this._showError(__('Sorry, unexpected error was occurred'), err);
        },

        showUpdateError: function (err) {
            this._showError(__('Sorry, the calendar updating was failed'), err);
        },

        _showError: function (message, err) {
            this._hideMask();
            messenger.showErrorMessage(message, err);
        },

        initCalendarContainer: function () {
            // init events container
            var eventsContainer = this.$el.find(this.options.eventsOptions.containerSelector);
            if (eventsContainer.length === 0) {
                throw new Error("Cannot find '" + this.options.eventsOptions.containerSelector + "' element.");
            }
            eventsContainer.empty();
            eventsContainer.append($(this.eventsTemplate()));
        },

        initializeFullCalendar: function () {
            var options, keys, self;
            // prepare options for jQuery FullCalendar control
            options = {
                aspectRatio: this.options.aspectRatio,
                contentHeight: this.options.contentHeight,
                height: this.options.height,
                selectHelper: true,
                events: _.bind(this.loadEvents, this),
                select: _.bind(this.select, this),
                eventClick: _.bind(this.eventClick, this),
                eventDrop: _.bind(this.eventDrop, this),
                eventResize: _.bind(this.eventResize, this),
                loading: _.bind(function (show) {
                    if (show) {
                        this.showLoadingMask();
                    } else {
                        this._hideMask();
                    }
                }, this)
            };
            keys = [
                'date', 'defaultView', 'editable', 'selectable',
                'header', 'allDayText', 'allDaySlot', 'buttonText',
                'titleFormat', 'columnFormat', 'timeFormat', 'axisFormat',
                'slotMinutes', 'snapMinutes', 'minTime', 'maxTime', 'slotEventOverlap',
                'firstDay', 'firstHour', 'monthNames', 'monthNamesShort', 'dayNames', 'dayNamesShort',
                'contentHeight'
            ];
            _.extend(options, _.pick(this.options.eventsOptions, keys));
            if (!_.isUndefined(options.date)) {
                options.date = dateTimeFormatter.applyTimeZoneCorrection(options.date);
                options.year = options.date.getFullYear();
                options.month = options.date.getMonth();
                options.date = options.date.getDate();
            }

            if (options.aspectRatio) {
                delete options.contentHeight;
                delete options.height;
            } else if (!options.contentHeight) {
                options.contentHeight = "auto";
                options.height = "auto";
            }

            var dateFormat = localeSettings.getVendorDateTimeFormat('moment', 'date', 'MMM D, YYYY');
            var timeFormat = localeSettings.getVendorDateTimeFormat('moment', 'time', 'h:mm A');
            // prepare FullCalendar specific date/time formats
            var isDateFormatStartedWithDay = dateFormat[0] === 'D';
            var weekFormat = isDateFormatStartedWithDay
                ? 'D MMMM YYYY'
                : 'MMMM D YYYY';

            options.titleFormat = {
                month: 'MMMM YYYY',
                week: weekFormat,
                day: 'dddd, ' + dateFormat
            };
            options.columnFormat = {
                month: 'ddd',
                week: 'ddd ' + dateFormat,
                day: 'dddd ' + dateFormat
            };
            options.timeFormat = {
                '': timeFormat,
                agenda: timeFormat + '{ - ' + timeFormat + '}'
            };
            options.axisFormat = timeFormat;


            self = this;
            options.viewDisplay = function () {
                self.setTimeline();
                setInterval(function () { self.setTimeline(); }, 5 * 60 * 1000);
            };
            options.windowResize = function () {
                self.setTimeline();
            };

            options.eventAfterRender = function (fcEvent, element) {
                var reminders = self.collection.get(fcEvent.id).get('reminders');
                if (reminders && _.keys(reminders).length) {
                    element.find('.fc-event-inner').append('<i class="icon icon-bell"></i>');
                } else {
                    element.find('.icon').remove();
                }
            };

            // create jQuery FullCalendar control
            options.timezone = "UTC";
            this.getCalendarElement().fullCalendar(options);
            this.enableEventLoading = true;
        },

        initializeConnectionsView: function () {
            var connectionsContainer, connectionsTemplate;
            // init connections container
            connectionsContainer = this.$el.find(this.options.connectionsOptions.containerSelector);
            if (connectionsContainer.length === 0) {
                throw new Error("Cannot find '" + this.options.connectionsOptions.containerSelector + "' element.");
            }
            connectionsContainer.empty();
            connectionsTemplate = _.template($(this.options.connectionsOptions.containerTemplateSelector).html());
            connectionsContainer.append($(connectionsTemplate()));

            // create a view for a list of connections
            this.connectionsView = new ConnectionView({
                el: connectionsContainer,
                collection: this.options.connectionsOptions.collection,
                calendar: this.options.calendar,
                itemTemplateSelector: this.options.connectionsOptions.itemTemplateSelector,
                colorManager: this.colorManager
            });

            this.listenTo(this.connectionsView, 'connectionAdd', this.onConnectionAdded);
            this.listenTo(this.connectionsView, 'connectionChange', this.onConnectionChanged);
            this.listenTo(this.connectionsView, 'connectionRemove', this.onConnectionDeleted);
        },

        loadConnectionColors: function () {
            var lastBackgroundColor = null;
            this.getConnectionCollection().each(_.bind(function (connection) {
                var obj = connection.toJSON();
                this.colorManager.applyColors(obj, function () {
                    return lastBackgroundColor;
                });
                this.colorManager.setCalendarColors(obj.calendarUid, obj.backgroundColor);
                if (obj.calendarAlias === 'user') {
                    lastBackgroundColor = obj.backgroundColor;
                }
            }, this));
        },

        render: function () {
            // init views
            this.initCalendarContainer();
            if (_.isUndefined(this.options.connectionsOptions.containerTemplateSelector)) {
                this.loadConnectionColors();
            } else {
                this.initializeConnectionsView();
            }
            // initialize jQuery FullCalendar control
            this.initializeFullCalendar();

            return this;
        },

        setTimeline: function () {
            var todayElement, parentDiv, timelineElement, curCalView, percentOfDay, curSeconds, topLoc, dayCol,
                calendarElement = this.getCalendarElement(),
                curTime = new Date();
            curTime = new Date(curTime.getTime() +
                curTime.getTimezoneOffset() * 60000 +
                this.options.eventsOptions.timezoneOffset * 60000);
            // this function is called every 5 minutes
            if (curTime.getHours() === 0 && curTime.getMinutes() <= 5) {
                // the day has changed
                todayElement = calendarElement.find('.fc-today');
                todayElement.removeClass('fc-today');
                todayElement.removeClass('fc-state-highlight');
                todayElement.next().addClass('fc-today');
                todayElement.next().addClass('fc-state-highlight');
            }

            parentDiv = calendarElement.find('.fc-agenda-slots:visible').parent();
            timelineElement = parentDiv.children('.timeline');
            if (timelineElement.length === 0) {
                // if timeline isn't there, add it
                timelineElement = $('<hr>').addClass('timeline');
                parentDiv.prepend(timelineElement);
            }

            curCalView = calendarElement.fullCalendar('getView');
            if (curCalView.visStart < curTime && curCalView.visEnd > curTime) {
                timelineElement.show();
            } else {
                timelineElement.hide();
            }

            curSeconds = (curTime.getHours() * 60 * 60) + (curTime.getMinutes() * 60) + curTime.getSeconds();
            percentOfDay = curSeconds / 86400; //24 * 60 * 60 = 86400, # of seconds in a day
            topLoc = Math.floor(parentDiv.height() * percentOfDay);
            timelineElement.css('top', topLoc + 'px');

            if (curCalView.name === 'agendaWeek') {
                // week view, don't want the timeline to go the whole way across
                dayCol = calendarElement.find('.fc-today:visible');
                if (dayCol.position() !== null) {
                    timelineElement.css({
                        left: (dayCol.position().left - 1) + 'px',
                        width: (dayCol.width() + 2) + 'px'
                    });
                }
            }
        }
    });
});
