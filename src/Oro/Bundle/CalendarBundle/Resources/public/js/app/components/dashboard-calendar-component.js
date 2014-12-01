/*jslint nomen: true*/
/*global define*/
define(function (require) {
    'use strict';
    var CalendarComponent = require('orocalendar/js/app/components/calendar-component'),
        widgetManager = require('oroui/js/widget-manager');

    var DashboardCalendarComponent = CalendarComponent.extend({
        renderCalendar: function () {
            CalendarComponent.prototype.renderCalendar.call(this);
            var allDayEventCount = 0,
                component = this,
                suggestedContentHeight,
                contentHeight;
            this.calendar
                .getCollection()
                .each(function (model) {
                    if (model.get("allDay")) {
                        allDayEventCount++;
                    }
                });
            suggestedContentHeight = Math.round(this.calendar.getCalendarElement().width() * 0.5);
            contentHeight = allDayEventCount < 5
                ? suggestedContentHeight
                : (allDayEventCount * 20) + suggestedContentHeight;
            this.calendar.getCalendarElement().fullCalendar("option", "contentHeight", contentHeight);

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
