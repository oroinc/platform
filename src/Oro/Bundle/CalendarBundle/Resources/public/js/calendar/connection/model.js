/*global define*/
define(['backbone', 'orocalendar/js/calendar/connection/collection'
    ], function (Backbone, ConnectionCollection) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/connection/model
     * @class   orocalendar.calendar.connection.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        /** @property */
        idAttribute: 'calendar',

        collection: ConnectionCollection,

        defaults: {
            color : null,
            backgroundColor : null,
            calendar: null,
            calendarName: null,
            owner: null,
            removable: false
        }
    });
});
