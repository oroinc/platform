/*jslint nomen:true*/
/*global define*/
define(['underscore', 'backbone', 'routing'
    ], function (_, Backbone, routing) {
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
            description : null,
            start: null,
            end: null,
            allDay: false,
            reminders: {},
            editable: false,
            removable: false,
            calendarAlias: null,
            calendar: null, // calendarId
            calendarUid: null // calculated automatically
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
                attrs = key || {};
                options = val;
            } else {
                attrs = {};
                attrs[key] = val;
            }

            options.contentType = 'application/json';
            options.data = JSON.stringify(
                _.extend({}, _.omit(this.toJSON(), ['calendarUid', 'editable', 'removable']), attrs || {})
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
