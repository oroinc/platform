/*jslint nomen:true*/
/*global define*/
define(['underscore', 'backbone', 'routing', 'moment', 'orolocale/js/locale-settings'
    ], function (_, Backbone, routing, moment, localeSettings) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/event/model
     * @class   orocalendar.calendar.event.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        route: 'oro_api_get_calendarevents',
        urlRoot: null,
        originalId: null, // original id received from a server

        defaults: {
            id: null, // original id is copied to originalId property and this attribute is replaced with calendarUid + originalId
            title : null,
            description : null,
            start: null,
            end: null,
            allDay: false,
            backgroundColor: null,
            reminders: {},
            editable: false,
            removable: false,
            calendarAlias: null,
            calendar: null, // calendarId
            calendarUid: null // calculated automatically, equals to calendarAlias + calendarId
        },

        initialize: function () {
            this.urlRoot = routing.generate(this.route);
            this._updateComputableAttributes();
            this.on('change:id change:calendarAlias change:calendar', this._updateComputableAttributes, this);

            // translate start and end to current timezone
            this._updateTimezone();
            this.on('change:start change:end change:timezone', this._updateTimezone, this);
        },

        url: function () {
            var url,
                id = this.id;

            this.id = this.originalId;
            url = Backbone.Model.prototype.url.call(this, arguments);
            this.id = id;

            return url;
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

            // save dates always in UTC
            if (attrs.start) {
                attrs.start = moment(attrs.start).zone(0).format();
            }
            if (attrs.end) {
                attrs.end = moment(attrs.end).zone(0).format();
            }

            options.contentType = 'application/json';
            options.data = JSON.stringify(_.extend(
                {id: this.originalId},
                _.omit(this.toJSON(), ['id', 'editable', 'removable', 'calendarUid', 'timezoneShift']),
                attrs || {}
            ));

            Backbone.Model.prototype.save.call(this, attrs, options);
        },

        _updateComputableAttributes: function () {
            var calendarAlias = this.get('calendarAlias'),
                calendarId = this.get('calendar'),
                calendarUid = calendarAlias && calendarId ? calendarAlias + '_' + calendarId : null;

            this.set('calendarUid', calendarUid);

            if (!this.originalId && this.id && calendarUid) {
                this.originalId = this.id;
                this.set('id', calendarUid + '_' + this.originalId);
            }
        },

        _updateTimezone: function () {
            /**
             * NOTE: Changes only it's timezone, the end and start properties still point at the same time as before
             */
            var start = moment(this.get('start')),
                end = moment(this.get('end')),
                timezoneShift = localeSettings.getTimeZoneShift();
            if (start.zone() !== timezoneShift) {
                this.attributes.start = start.zone(timezoneShift).format();
            }
            if (end.zone() !== timezoneShift) {
                this.attributes.end = end.zone(timezoneShift).format();
            }
        }
    });
});
