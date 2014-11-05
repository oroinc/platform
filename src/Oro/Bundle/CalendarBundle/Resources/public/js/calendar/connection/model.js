/*jslint nomen:true*/
/*global define*/
define(['underscore', 'backbone', 'routing', 'orocalendar/js/calendar/connection/collection'
    ], function (_, Backbone, routing, ConnectionCollection) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/connection/model
     * @class   orocalendar.calendar.connection.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        /** @property */
        collection: ConnectionCollection,
        route: 'oro_api_post_calendar_connection',
        urlRoot: null,

        defaults: {
            id: null,
            targetCalendar: null,
            calendarAlias: null,
            calendar: null, // calendarId
            calendarUid: null, // calculated automatically
            position: 0,
            visible: true,
            color: null,
            backgroundColor: null,
            calendarName: null,
            removable: true
        },

        initialize: function () {
            this.urlRoot = routing.generate(this.route);
            this._updateCalendarAttribute();
            this.on('change:calendarAlias change:calendarId', this._updateCalendarAttribute, this);
        },

        save: function (key, val, options) {
            var attrs;

            // Handle both `"key", value` and `{key: value}` -style arguments.
            if (key == null || typeof key === 'object') {
                attrs = key;
                options = val;
            } else {
                attrs = {};
                attrs[key] = val;
            }

            options.contentType = 'application/json';
            options.data = JSON.stringify(
                _.extend({}, _.omit(this.toJSON(), ['calendarUid', 'calendarName', 'removable']), attrs || {})
            );

            Backbone.Model.prototype.save.call(this, attrs, options);
        },

        _updateCalendarAttribute: function () {
            var calendarAlias = this.get('calendarAlias'),
                calendarId = this.get('calendar'),
                calendarUid = calendarAlias && calendarId ? calendarAlias + '_' + calendarId : null;
            this.set('calendarUid', calendarUid);
        }
    });
});
