define(function (require) {
    'use strict';
    var GuestsPlugin,
        _ = require('underscore'),
        BasePlugin = require('oroui/js/app/plugins/base/plugin'),
        GuestNotifierView = require('orocalendar/js/app/views/guest-notifier-view');

    GuestsPlugin = BasePlugin.extend({
        enable: function () {
            this.listenTo(this.main, 'event:added', this.onEventAdded);
            this.listenTo(this.main, 'event:changed', this.onEventChanged);
            this.listenTo(this.main, 'event:deleted', this.onEventDeleted);
            this.listenTo(this.main, 'event:beforeSave', this.onEventBeforeSave);
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
                    return c.get('calendarAlias') === alias &&
                        this.collection.get(c.get('calendarUid') + '_' + parentEventId);
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
        hasGuestEvents: function (eventModel) {
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
        findGuestEvents: function (eventModel) {
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
            if (this.hasGuestEvents(eventModel)) {
                this.main.updateEvents();
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
            if (this.hasGuestEvents(eventModel)) {
                if (eventModel.hasChanged('invitedUsers')) {
                    eventModel.once('sync', this.main.updateEvents, this.main);
                    return;
                }
                // update linked events
                guestEvents = this.findGuestEvents(eventModel);
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
            if (this.hasGuestEvents(eventModel)) {
                // remove guests
                guestEvents = this.findGuestEvents(eventModel);
                for (i = 0; i < guestEvents.length; i++) {
                    this.main.getCalendarElement().fullCalendar('removeEvents', guestEvents[i].id);
                    this.main.collection.remove(guestEvents[i]);
                    guestEvents[i].dispose();
                }
            }
        },

        /**
         * "event:beforeSave" callback.
         *
         * @param eventModel
         * @param {array} promises script will wait execution of all promises before save
         * @param {object} attrs to be set on event model
         */
        onEventBeforeSave: function (eventModel, promises, attrs) {
            if (this.hasGuestEvents(eventModel)) {
                var deferredConfirmation = $.Deferred(), cleanUp;
                promises.push(deferredConfirmation);

                if (!this.modal) {
                    cleanUp = _.bind(function () {
                        this.modal.dispose();
                        delete this.modal;
                    }, this);

                    this.modal = GuestNotifierView.createConfirmNotificationDialog();

                    this.modal.on('ok', _.bind(function () {
                        attrs.notifyInvitedUsers = true;
                        deferredConfirmation.resolve();
                        _.defer(cleanUp);
                    }, this));

                    this.modal.on('cancel', _.bind(function () {
                        attrs.notifyInvitedUsers = false;
                        deferredConfirmation.resolve();
                        _.defer(cleanUp);
                    }, this));

                    this.modal.on('close', _.bind(function () {
                        deferredConfirmation.reject();
                        _.defer(cleanUp);
                    }, this));

                    this.modal.open();
                }
            }
        }
    });

    return GuestsPlugin;
});
