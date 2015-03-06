define(function (require) {
    'use strict';
    var GuestsPlugin,
        _ = require('underscore'),
        BasePlugin = require('oroui/js/app/plugins/base/plugin'),
        mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools');

    GuestsPlugin = BasePlugin.extend({
        enable: function () {
            this.listenTo(this.main, 'event:added', this.onEventAdded);
            this.listenTo(this.main, 'event:changed', this.onEventChanged);
            this.listenTo(this.main, 'event:deleted', this.onEventDeleted);
            GuestsPlugin.__super__.enable.call(this);
        },

        // no disable() function 'cause attached callbacks will be removed in parent disable method

        /**
         * Verifies if event is a guest event
         *
         * @param eventModel
         * @returns {boolean}
         */
        hasParentEvent: function (eventModel) {
            var result = false,
                parentEventId = eventModel.get('parentEventId'),
                alias = eventModel.get('calendarAlias');
            if (parentEventId) {
                result = Boolean(this.main.getConnectionCollection().find(function (c) {
                    return c.get('calendarAlias') === alias && this.collection.get(c.get('calendarUid') + '_' + parentEventId);
                }, this));
            }
            return result;
        },

        /**
         * Verifies if event has a loaded guest events
         *
         * @param eventModel
         * @returns {boolean}
         */
        hasGuestEvent: function (eventModel) {
            var result = false,
                guests = eventModel.get('invitedUsers');
            guests = _.isNull(guests) ? [] : guests;
            if (eventModel.hasChanged('invitedUsers') && !_.isEmpty(eventModel.previous('invitedUsers'))) {
                guests = _.union(guests, eventModel.previous('invitedUsers'));
            }
            if (!_.isEmpty(guests)) {
                result = Boolean(this.main.getConnectionCollection().find(function (connection) {
                    return -1 !== guests.indexOf(connection.get('userId'));
                }, this));
            }
            return result;
        },

        /**
         * Returns linked guest events
         *
         * @param eventModel
         * @returns {boolean}
         */
        findGuestEvent: function (eventModel) {
            return this.main.collection.where({
                parentEventId: '' + eventModel.originalId
            });
        },

        /**
         * "event:added" callback
         *
         * @param eventModel
         */
        onEventAdded: function (eventModel) {
            eventModel.set('editable', eventModel.get('editable') && !this.hasParentEvent(eventModel), {silent: true});
            if (this.hasGuestEvent(eventModel)) {
                // refetch if there are visible guest events
                if (!eventModel.changing) {
                    this.main.smartRefetch();
                } else {
                    eventModel.once('sync', this.main.smartRefetch, this.main);
                }
            }
        },

        /**
         * "event:changed" callback
         *
         * @param eventModel
         */
        onEventChanged: function (eventModel) {
            var guestEvents, i, updatedAttrs;
            eventModel.set('editable', eventModel.get('editable') && !this.hasParentEvent(eventModel), {silent: true});
            if (this.hasGuestEvent(eventModel)) {
                if (eventModel.changed.invitedUsers) {
                    if (!eventModel.changing) {
                        this.main.smartRefetch();
                    } else {
                        eventModel.once('sync', this.main.smartRefetch, this.main);
                    }
                    return;
                }
                // update linked events
                guestEvents = this.findGuestEvent(eventModel);
                updatedAttrs = _.pick(eventModel.changed, ['start', 'end', 'allDay', 'title', 'description']);
                for (i = 0; i < guestEvents.length; i++) {
                    // fill with updated attributes in parent
                    guestEvents[i].set(updatedAttrs);
                }
            }
        },

        /**
         * "event:deleted" callback
         *
         * @param eventModel
         */
        onEventDeleted: function (eventModel) {
            var guestEvents, i;
            if (this.hasGuestEvent(eventModel)) {
                // remove guests
                guestEvents = this.findGuestEvent(eventModel);
                for (i = 0; i < guestEvents.length; i++) {
                    this.main.getCalendarElement().fullCalendar('removeEvents', guestEvents[i].id);
                    this.main.collection.remove(guestEvents[i]);
                    guestEvents[i].dispose();
                }
            }
        }
    });

    return GuestsPlugin;
});
