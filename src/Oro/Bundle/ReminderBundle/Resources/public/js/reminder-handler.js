/*global define*/
define(
    ['jquery', 'orosync/js/sync', 'oroui/js/messenger', 'routing', 'underscore', 'oronavigation/js/navigation',
    'oroui/js/mediator'],
    function ($, sync, messenger, routing, _, Navigation, mediator) {
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
            init: function (id, wampEnable) {
                var self = this;

                mediator.on('page-rendered hash_navigation_request:complete', function () {
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
            initWamp: function (id) {
                var self = this;
                sync.subscribe('oro/reminder/remind_user_' + id, function (data) {
                    var reminders = JSON.parse(data);
                    self.addReminders(reminders);
                    self.showReminders();
                });
            },

            /**
             * Set reminders
             *
             * @param {Array} reminders
             */
            setReminders: function (reminders) {
                var self = this;
                this.reminders = {};
                _.each(reminders, function (reminder) {
                    self.addReminder(reminder);
                });
            },

            /**
             * Add reminders
             *
             * @param {Array} reminders
             */
            addReminders: function (reminders) {
                var self = this;
                _.each(reminders, function (reminder) {
                    self.addReminder(reminder);
                });
            },

            /**
             * Add reminder
             *
             * @param {Object} newReminder
             */
            addReminder: function (newReminder) {
                var uniqueId = newReminder.uniqueId;
                var newId = newReminder.id;
                var oldReminder = this.reminders[uniqueId];
                if (!oldReminder) {
                    var removeDate = this.removeDates[uniqueId];
                    var currentDate = new Date();
                    // If was already removed less then 60 secs ago, ignore it
                    if (removeDate && currentDate.getTime() - removeDate.getTime() < 60000) {
                        return;
                    }
                    this.reminders[uniqueId] = newReminder;
                } else if (oldReminder.id != newId) {
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
            removeReminder: function (uniqueId) {
                var reminder = this.reminders[uniqueId];
                if (!reminder) {
                    return;
                }
                var url = routing.generate('oro_api_post_reminder_shown');
                var removeIds = reminder.duplicateIds || [];
                removeIds.push(reminder.id);

                $.post(url, { 'ids': removeIds });

                this.removeDates[uniqueId] = new Date();

                delete this.reminders[uniqueId];
            },

            /**
             * Show reminders
             */
            showReminders: function () {
                var self = this;

                // Remove all reminders
                $('.alert-reminder').remove();

                _.each(this.reminders, function (reminder, uniqueId) {
                    var message = this.getReminderMessage(reminder);
                    message += '(<a class="reminder-dismiss-link" data-id="' + reminder.id + '" data-unique-id="'
                        + reminder.uniqueId + '" href="javascript:void(0);">dismiss</a>)';

                    var actions = messenger.notificationFlashMessage('reminder', message, {delay: false, flash: false});
                    var data = { actions: actions, uniqueId: uniqueId };

                    $('.reminder-dismiss-link[data-id="' + reminder.id + '"]')
                        .bind('click', data, function (event) {
                            self.removeReminder(event.data.uniqueId);
                            event.data.actions.close();
                        });

                }, this);

                var navigation = Navigation.getInstance();

                $('.alert-reminder .close').unbind('click').bind('click', function () {
                    $(this).parents('.alert-reminder').find('.reminders_dismiss_link').click();
                });

                $('.alert-reminder .hash-navigation-link').unbind('click').bind('click', function (event) {
                    event.preventDefault();
                    var url = $(this).attr('href');
                    navigation.setLocation(url);
                });
            },

            /**
             * @param {object} reminder
             * @returns {string}
             */
            getReminderMessage: function (reminder) {
                var message = '';
                try {
                    message = '<i class="icon-bell"></i>';
                    var template = $('.reminder_templates[data-identifier="' + reminder.templateId + '"]').html();
                    if ($.trim(template) == '') {
                        template = $('.reminder_templates[data-identifier="default"]').html();
                    }
                    message += _.template(template, reminder);
                } catch (Exception) {
                    // Suppress possible exceptions during template processing
                }
                return message;
            }
        };
    });
