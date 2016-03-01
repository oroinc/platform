define([
    'underscore',
    'backbone',
    'routing'
], function(_, Backbone, routing) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/connection/model
     * @class   orocalendar.calendar.connection.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        route: 'oro_api_post_calendar_connection',
        urlRoot: null,

        /**
         * This property can be used to indicate whether calendar events
         * should be reloaded or not after a calendar connection is changed.
         * To force events reloading set this property to true.
         * To prohibit events reloading set this property to false.
         * @property
         */
        reloadEventsRequest: null,

        defaults: {
            id: null,
            targetCalendar: null,
            calendarAlias: null,
            calendar: null, // calendarId
            calendarUid: null, // calculated automatically, equals to calendarAlias + calendarId
            position: 0,
            visible: true,
            backgroundColor: null,
            calendarName: null,
            userId: null,
            removable: true,
            canAddEvent: false,
            canEditEvent: false,
            canDeleteEvent: false,
            options: null
        },

        initialize: function() {
            this.urlRoot = routing.generate(this.route);
            this._updateCalendarUidAttribute();
            this.on('change:calendarAlias change:calendar', this._updateCalendarUidAttribute, this);
        },

        save: function(key, val, options) {
            var attrs;

            // Handle both `"key", value` and `{key: value}` -style arguments.
            if (key === null || key === undefined || typeof key === 'object') {
                attrs = key;
                options = val;
            } else {
                attrs = {};
                attrs[key] = val;
            }

            options.contentType = 'application/json';
            options.data = JSON.stringify(
                _.extend({}, _.omit(
                    this.toJSON(),
                    ['calendarUid', 'calendarName', 'userId', 'removable',
                        'canAddEvent', 'canEditEvent', 'canDeleteEvent']
                ), attrs || {})
            );

            Backbone.Model.prototype.save.call(this, attrs, options);
        },

        toJSON: function(options) {
            return _.omit(Backbone.Model.prototype.toJSON.call(this, options), ['options']);
        },

        _updateCalendarUidAttribute: function() {
            var calendarAlias = this.get('calendarAlias');
            var calendarId = this.get('calendar');
            var calendarUid = calendarAlias && calendarId ? calendarAlias + '_' + calendarId : null;
            this.set('calendarUid', calendarUid);
        }
    });
});
