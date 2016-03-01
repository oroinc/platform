define(function(require) {
    'use strict';

    var CalendarComponent = require('orocalendar/js/app/components/calendar-component');
    var widgetManager = require('oroui/js/widget-manager');
    var moment = require('moment');

    var DashboardCalendarComponent = CalendarComponent.extend({
        renderCalendar: function() {
            DashboardCalendarComponent.__super__.renderCalendar.call(this);
            this.adoptWidgetActions();
        },
        adoptWidgetActions: function() {
            var component = this;
            function roundToHalfAnHour(moment) {
                var minutesToAdd = moment.minutes() < 30 ? 30 : 60;
                return moment.startOf('hour').add(minutesToAdd, 'm');
            }
            widgetManager.getWidgetInstance(this.options.widgetId, function(widget) {
                widget.getAction('new-event', 'adopted', function(newEventAction) {
                    newEventAction.on('click', function() {
                        component.calendar.showAddEventDialog({
                            start: roundToHalfAnHour(moment.utc()),
                            end: roundToHalfAnHour(moment.utc()).add(1, 'h')
                        });
                    });
                });
            });
        }
    });

    return DashboardCalendarComponent;
});
