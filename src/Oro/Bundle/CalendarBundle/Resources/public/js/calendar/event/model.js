/*global define*/
define(['backbone', 'routing'], function (Backbone, routing) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/event/model
     * @class   orocalendar.calendar.event.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        route: 'oro_api_get_calendarevents',
        urlRoot: null,

        defaults: {
            id: null,
            title : null,
            start: null,
            end: null,
            allDay: false,
            reminder: false,
            editable: false,
            removable: false
        },

        initialize: function () {
            this.urlRoot = routing.generate(this.route);
        }
    });
});
