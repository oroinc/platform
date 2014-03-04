/*global define*/
define(['backbone', 'routing', 'orocalendar/js/calendar/connection/model'
    ], function (Backbone, routing, ConnectionModel) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/connection/collection
     * @class   oro.calendar.connection.Collection
     * @extends Backbone.Collection
     */
    return Backbone.Collection.extend({
        route: 'oro_api_get_calendar_connections',
        url: null,
        model: ConnectionModel,

        /**
         * Sets a calendar this collection works with
         *
         * @param {int} calendarId
         */
        setCalendar: function (calendarId) {
            this.url = routing.generate(this.route, {id: calendarId});
        }
    });
});
