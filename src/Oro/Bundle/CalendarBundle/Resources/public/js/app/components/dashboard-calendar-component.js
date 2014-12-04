/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';
    var CalendarComponent = require('orocalendar/js/app/components/calendar-component'),
        widgetManager = require('oroui/js/widget-manager'),
        moment = require('moment');

    var DashboardCalendarComponent = CalendarComponent.extend({
        renderCalendar: function () {
            DashboardCalendarComponent.__super__.renderCalendar.call(this);
            this.adoptWidgetActions();
        },
        adoptWidgetActions: function () {
            var component = this;
            function roundToHalfAnHour(moment) {
                return moment.startOf('hour').add((moment.minutes() < 30 ? 30 : 60), 'm');
            }
            widgetManager.getWidgetInstance(this.options.widgetId, function (widget) {
                widget.getAction('new-event', 'adopted', function(newEventAction) {
                    newEventAction.on('click', function () {
                        component.calendar.select(roundToHalfAnHour(moment()), roundToHalfAnHour(moment()).add(1, 'h'));
                    });
                });
            });
        }
    });

    return DashboardCalendarComponent;
});
