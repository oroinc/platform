define(
    ['jquery', 'orosync/js/sync', 'oroui/js/messenger', 'routing', 'underscore', 'oroui/js/mediator'],
    function($, sync, messenger, routing, _, mediator) {
        'use strict';

        const console = window.console;

        /**
         * @export ororeminder/js/reminder-handler
         * @class ororeminder.ReminderHandler
         */
        return {
            removeDates: {},

            reminders: {},

            /**
             * Initialize event listening
             *
             * @param {integer} id Current user id
             * @param {Boolean} wampEnable Is WAMP enabled
             */
            init: function(id, wampEnable) {
                const self = this;

                mediator.on('page:afterChange', function() {
                    self.showReminders();
                });

                if (wampEnable) {
                    this.initWamp(id);
                }
            },

            /**
             * Initialize WAMP subscribing
             *
             * @param {integer} id Current user id
             */
            initWamp: function(id) {
                const self = this;
                sync.subscribe('oro/reminder_remind/' + id, function(reminders) {
                    self.addReminders(reminders);
                    self.showReminders();
                });
            },

            /**
             * Set reminders
             *
             * @param {Array} reminders
             */
            setReminders: function(reminders) {
                const self = this;
                this.reminders = {};
                _.each(reminders, function(reminder) {
                    self.addReminder(reminder);
                });
            },

            /**
             * Add reminders
             *
             * @param {Array} reminders
             */
            addReminders: function(reminders) {
                const self = this;
                _.each(reminders, function(reminder) {
                    self.addReminder(reminder);
                });
            },

            /**
             * Add reminder
             *
             * @param {Object} newReminder
             */
            addReminder: function(newReminder) {
                const uniqueId = newReminder.uniqueId;
                const newId = newReminder.id;
                const oldReminder = this.reminders[uniqueId];
                if (!oldReminder) {
                    const removeDate = this.removeDates[uniqueId];
                    const currentDate = new Date();
                    // If was already removed less then 60 secs ago, ignore it
                    if (removeDate && currentDate.getTime() - removeDate.getTime() < 60000) {
                        return;
                    }
                    this.reminders[uniqueId] = newReminder;
                } else if (oldReminder.id !== newId) {
                    oldReminder.duplicateIds = oldReminder.duplicateIds || [];
                    if (_.indexOf(oldReminder.duplicateIds, newId)) {
                        oldReminder.duplicateIds.push(newId);
                    }
                }
            },

            /**
             * Remove reminder by uinque id
             *
             * @param {integer} uniqueId
             */
            removeReminder: function(uniqueId) {
                const reminder = this.reminders[uniqueId];
                if (!reminder) {
                    return;
                }
                const url = routing.generate('oro_api_post_reminder_shown');
                const removeIds = reminder.duplicateIds || [];
                removeIds.push(reminder.id);

                $.post(url, {ids: removeIds});

                this.removeDates[uniqueId] = new Date();

                delete this.reminders[uniqueId];
            },

            /**
             * Show reminders
             */
            showReminders: function() {
                const self = this;

                // Remove all reminders
                $('.alert-reminder').remove();

                _.each(this.reminders, function(reminder, uniqueId) {
                    let message = this.getReminderMessage(reminder);
                    message += '(<a class="reminder-dismiss-link" data-id="' + reminder.id + '" data-unique-id="' +
                        reminder.uniqueId + '" href="#">dismiss</a>)';

                    const actions =
                        messenger.notificationFlashMessage('reminder', message, {delay: false, flash: false});
                    const data = {actions: actions, uniqueId: uniqueId};

                    $('.reminder-dismiss-link[data-id="' + reminder.id + '"]')
                        .bind('click', data, function(event) {
                            self.removeReminder(event.data.uniqueId);
                            event.data.actions.close();
                        });
                }, this);

                $('.alert-reminder .close').unbind('click').bind('click', function() {
                    $(this).parents('.alert-reminder').find('.reminders_dismiss_link').click();
                });

                $('.alert-reminder .hash-navigation-link').unbind('click').bind('click', function(event) {
                    event.preventDefault();
                    const url = $(this).attr('href');
                    mediator.execute('redirectTo', {url: url});
                });
            },

            /**
             * @param {object} reminder
             * @returns {string}
             */
            getReminderMessage: function(reminder) {
                let message = '';
                try {
                    const template = $('.reminder_templates[data-identifier="' + reminder.templateId + '"]');
                    let content = '';

                    if (template.length) {
                        content = template.html().trim();
                    }

                    if (!content) {
                        content = $('.reminder_templates[data-identifier="default"]').html();
                    }

                    message += _.template(content)(reminder);
                } catch (ex) {
                    // Suppress possible exceptions during template processing
                    if (console && (typeof console.log === 'function')) {
                        console.log('Exception occurred when compiling reminder template', ex);
                    }
                }
                return message;
            }
        };
    });
