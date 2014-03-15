/* jshint devel:true*/
/*global define, console*/
define(['underscore', 'backbone', 'orotranslation/js/translator', 'oroui/js/app', 'oroui/js/messenger', 'oroui/js/loading-mask',
    'orocalendar/js/calendar/event/collection', 'orocalendar/js/calendar/event/model', 'orocalendar/js/calendar/event/view',
    'orocalendar/js/calendar/connection/collection', 'orocalendar/js/calendar/connection/view', 'orocalendar/js/calendar/color-manager',
    'orolocale/js/formatter/datetime', 'jquery.fullcalendar'
    ], function (_, Backbone, __, app, messenger, LoadingMask,
         EventCollection, EventModel, EventView,
         ConnectionCollection, ConnectionView, ColorManager, dateTimeFormatter) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  orocalendar/js/calendar
     * @class   orocalendar.Сalendar
     * @extends Backbone.View
     */
    return Backbone.View.extend({
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

        options: {
            eventsOptions: {
                editable: true,
                removable: true,
                collection: null,
                itemFormTemplateSelector: null,
                itemFormDeleteButtonSelector: null,
                calendar: null
            },
            connectionsOptions: {
                collection: null,
                containerTemplateSelector: null
            }
        },

        /**
         * this property is used to prevent loading of events from a server when the calendar object is created
         *  @property {bool}
         */
        enableEventLoading: false,
        fullCalendar: null,
        eventView: null,
        loadingMask: null,
        colorManager: null,

        initialize: function () {
            // init event collection
            this.options.collection = this.options.collection || new EventCollection();
            this.options.collection.setCalendar(this.options.calendar);
            this.options.collection.subordinate = this.options.eventsOptions.subordinate;

            // set options for new events
            this.options.newEventEditable = this.options.eventsOptions.editable;
            this.options.newEventRemovable = this.options.eventsOptions.removable;

            // subscribe to event collection events
            this.listenTo(this.getCollection(), 'add', this.onEventAdded);
            this.listenTo(this.getCollection(), 'change', this.onEventChanged);
            this.listenTo(this.getCollection(), 'destroy', this.onEventDeleted);

            this.colorManager = new ColorManager();
        },

        getEventsView: function (model) {
            if (!this.eventView) {
                // create a view for event details
                this.eventView = new EventView({
                    model: model,
                    calendar: this.options.calendar,
                    formTemplateSelector: this.options.eventsOptions.itemFormTemplateSelector
                });
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
            return this.options.collection;
        },

        getCalendarElement: function () {
            if (!this.fullCalendar) {
                this.fullCalendar = this.$el.find(this.selectors.calendar);
            }
            return this.fullCalendar;
        },

        handleEventViewAdd: function (eventModel) {
            this.getCollection().add(eventModel);
        },

        onEventAdded: function (eventModel) {
            var fcEvent = eventModel.toJSON();
            this.prepareViewModel(fcEvent);

            this.getCalendarElement().fullCalendar('renderEvent', fcEvent);
        },

        onEventChanged: function (eventModel) {
            var fcEvent = this.getCalendarElement().fullCalendar('clientEvents', eventModel.get('id'))[0];
            // copy all fields, except id, from event to fcEvent
            fcEvent = _.extend(fcEvent, _.pick(eventModel.attributes, _.keys(_.omit(fcEvent, ['id']))));
            this.prepareViewModel(fcEvent);
            this.getCalendarElement().fullCalendar('updateEvent', fcEvent);
        },

        onEventDeleted: function (eventModel) {
            this.getCalendarElement().fullCalendar('removeEvents', eventModel.id);
        },

        onConnectionAddedOrDeleted: function () {
            this.getCalendarElement().fullCalendar('refetchEvents');
        },

        onConnectionChanged: function () {

        },

        select: function (start, end) {
            if (!this.eventView) {
                try {
                    // TODO: All date values must be in UTC representation according to config timezone,
                    // https://magecore.atlassian.net/browse/BAP-2203
                    var eventModel = new EventModel({
                        start: this.formatDateTimeForModel(start),
                        end: this.formatDateTimeForModel(end),
                        editable: this.options.newEventEditable,
                        removable: this.options.newEventRemovable
                    });
                    this.getEventsView(eventModel).render();
                } catch (err) {
                    this.showError(err);
                }
            }
        },

        eventClick: function (fcEvent) {
            if (!this.eventView) {
                try {
                    var eventModel = this.getCollection().get(fcEvent.id);
                    this.getEventsView(eventModel).render();
                } catch (err) {
                    this.showError(err);
                }
            }
        },

        eventDropOrResize: function (fcEvent) {
            this.showSavingMask();
            try {
                this.getCollection()
                    .get(fcEvent.id)
                    .save(
                        {
                            start: this.formatDateTimeForModel(fcEvent.start),
                            end: this.formatDateTimeForModel(!_.isNull(fcEvent.end) ? fcEvent.end : fcEvent.start)
                        },
                        {
                            success: _.bind(this._hideMask, this),
                            error: _.bind(function (model, response) {
                                this.showSaveEventError(response.responseJSON);
                            }, this)
                        }
                    );
            } catch (err) {
                this.showLoadEventsError(err);
            }
        },

        loadEvents: function (start, end, callback) {
            var onEventsLoad = _.bind(function () {
                var fcEvents = this.getCollection().toJSON();
                this.prepareViewModels(fcEvents);
                this._hideMask();
                callback(fcEvents);
            }, this);

            try {
                this.getCollection().setRange(
                    this.formatDateTimeForModel(start),
                    this.formatDateTimeForModel(end)
                );
                if (this.enableEventLoading) {
                    // load events from a server
                    this.getCollection().fetch({
                        success: onEventsLoad,
                        error: _.bind(function (collection, response) {
                            callback({});
                            this.showLoadEventsError(response.responseJSON);
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

        prepareViewModels : function (fcEvents) {
            _.each(fcEvents, this.prepareViewModel, this);
        },

        prepareViewModel : function (fcEvent) {
            // convert start and end dates from backend formatted string to Date object
            fcEvent.start = dateTimeFormatter.unformatBackendDateTime(fcEvent.start);
            fcEvent.end = dateTimeFormatter.unformatBackendDateTime(fcEvent.end);
            // set an event text and background colors the same as the owning calendar
            var colors = this.colorManager.getCalendarColors(fcEvent.calendar);
            fcEvent.textColor = colors.color;
            fcEvent.color = colors.backgroundColor;
        },

        formatDateTimeForModel: function (date) {
            return dateTimeFormatter.convertDateTimeToBackendFormat(date);
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
            this._showError(err, __('Sorry, calendar events were not loaded correctly'));
        },

        showSaveEventError: function (err) {
            this._showError(err, __('Sorry, calendar event was not saved correctly'));
        },

        showError: function (err) {
            this._showError(err, __('Sorry, unexpected error was occurred'));
        },

        _showError: function (err, message) {
            this._hideMask();
            if (!_.isUndefined(console)) {
                console.error(_.isUndefined(err.stack) ? err : err.stack);
            }
            var msg = message;
            if (app.debug) {
                if (!_.isUndefined(err.message)) {
                    msg += ': ' + err.message;
                } else if (!_.isUndefined(err.errors) && _.isArray(err.errors)) {
                    msg += ': ' + err.errors.join();
                } else if (_.isString(err)) {
                    msg += ': ' + err;
                }
            }
            messenger.notificationFlashMessage('error', msg);
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
                selectHelper: true,
                events: _.bind(this.loadEvents, this),
                select: _.bind(this.select, this),
                eventClick: _.bind(this.eventClick, this),
                eventDrop: _.bind(this.eventDropOrResize, this),
                eventResize: _.bind(this.eventDropOrResize, this),
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
                if (_.isString(options.date)) {
                    options.date = $.fullCalendar.parseISO8601(options.date, true);
                }
                options.year = options.date.getFullYear();
                options.month = options.date.getMonth();
                options.date = options.date.getDate();
            }

            //Fix aspect ration to prevent double scroll for week and day views.
            options.viewRender = _.bind(function (view) {
                if (view.name !== 'month') {
                    this.getCalendarElement().fullCalendar('option', 'aspectRatio', 1.0);
                } else {
                    this.getCalendarElement().fullCalendar('option', 'aspectRatio', 1.35);
                }
            }, this);

            self = this;
            options.viewDisplay = function () {
                self.setTimeline();
                setInterval(function () { self.setTimeline(); }, 5 * 60 * 1000);
            };
            options.windowResize = function () {
                self.setTimeline();
            };

            options.eventAfterRender = function(event, element) {
                var reminders = self.getCollection().get(event.id).get('reminders');
                if (reminders && _.keys(reminders).length) {
                    element.find('.fc-event-inner').append('<i class="icon icon-bell"></i>');
                } else {
                    element.find('.icon').remove();
                }
            };

            // create jQuery FullCalendar control
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

            this.listenTo(this.connectionsView, 'connectionAdd', this.onConnectionAddedOrDeleted);
            this.listenTo(this.connectionsView, 'connectionChange', this.onConnectionChanged);
            this.listenTo(this.connectionsView, 'connectionRemove', this.onConnectionAddedOrDeleted);
        },

        loadConnectionColors: function () {
            var lastBackgroundColor = null;
            this.options.connectionsOptions.collection.each(_.bind(function (connection) {
                var obj = connection.toJSON();
                this.colorManager.applyColors(obj, function () {
                    return lastBackgroundColor;
                });
                this.colorManager.setCalendarColors(obj.calendar, obj.color, obj.backgroundColor);
                lastBackgroundColor = obj.backgroundColor;
            }, this));
        },

        render: function () {
            // init views
            this.initCalendarContainer();
            if (_.isUndefined(this.options.connectionsOptions.containerTemplateSelector)) {
                // connections management is not required - just load connections' colors and forged about connections
                this.loadConnectionColors();
                delete this.options.connectionsOptions;
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
