/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';
    var CalendarComponent = require('orocalendar/js/app/components/calendar-component'),
        widgetManager = require('oroui/js/widget-manager');

    var DashboardCalendarComponent = CalendarComponent.extend({
        renderCalendar: function () {
            DashboardCalendarComponent.__super__.renderCalendar.call(this);
            this.adoptWidgetActions();
        },
        adoptWidgetActions: function () {
            var component = this;
            widgetManager.getWidgetInstance(this.options.widgetId, function (widget) {
                widget.getAction('new-event', 'adopted', function(newEventAction) {
                    newEventAction.on('click', function (e) {
                        var currentDate = new Date();
                        component.calendar.select(currentDate, currentDate);
                    });
                });
            });
        }
    });

    return DashboardCalendarComponent;
});
