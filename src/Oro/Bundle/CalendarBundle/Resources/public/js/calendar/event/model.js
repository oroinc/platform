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
            parentEventId: null,
            invitationStatus: null,
            invitedUsers: null,
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
            var attrs, modelData;

            // Handle both `"key", value` and `{key: value}` -style arguments.
            if (key == null || typeof key === 'object') {
                attrs = key || {};
                options = val;
            } else {
                attrs = {};
                attrs[key] = val;
            }

            modelData = _.extend(
                {id: this.originalId},
                _.omit(
                    this.toJSON(),
                    ['id', 'editable', 'removable', 'calendarUid', 'parentEventId', 'invitationStatus']
                ),
                attrs || {}
            );
            modelData.invitedUsers = modelData.invitedUsers ? modelData.invitedUsers.join(',') : undefined;

            options.contentType = 'application/json';
            options.data = JSON.stringify(modelData);

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

        validate: function (attrs) {
            var errors = [];

            if (attrs.start > attrs.end) {
                errors.push('oro.calendar.error_message.event_model.end_date_earlier_than_start');
            }

            return errors.length ? errors : null;
        },

        getInvitationStatus: function () {
            var invitationStatus = this.get('invitationStatus'),
                invitedUsers = this.get('invitedUsers');
            if (!invitationStatus && invitedUsers && invitedUsers.length) {
                invitationStatus = 'accepted';
            }
            return invitationStatus;
        }
    });
});
