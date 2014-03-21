/*global define*/
define(
    ['jquery', 'orosync/js/sync', 'oroui/js/messenger', 'routing', 'underscore', 'oronavigation/js/navigation'],
    function ($, sync, messenger, routing, _, Navigation) {
        /**
         * @export ororeminder/js/subscriber
         */
        return {
            /**
             * @param {integer} id Current user id
             */
            init: function (id) {
                var self = this;

                sync.subscribe('oro/reminder/remind_user_' + id, function (messageParams) {
                    messageParams = JSON.parse(messageParams);
                    $('.reminders_dismiss_link[data-unique-id="' + messageParams.uniqueId + '"]').trigger('click');
                    self.showReminders([messageParams]);
                });
            },

            /**
             * @param {array} messageParamsArray
             * @return {object}
             */
            removeDuplicate: function (messageParamsArray) {
                var uniqueReminders = {};
                messageParamsArray.reduce(function (previous, current) {
                    if (previous[current.uniqueId]) {
                        previous[current.uniqueId].duplicateIds.push(current.id);
                    } else {
                        current.duplicateIds = [];
                        previous[current.uniqueId] = current;
                    }
                    return previous;
                }, uniqueReminders);
                return _.toArray(uniqueReminders);
            },

            /**
             * @param {array} messageParamsArray
             */
            showReminders: function (messageParamsArray) {
                _.each(this.removeDuplicate(messageParamsArray), function (messageObject) {
                    var message = this.reminderTextConstructor(messageObject);
                    message += '(<a class="reminders_dismiss_link" data-id="' + messageObject.id + '" data-unique-id="'
                        + messageObject.uniqueId + '" href="javascript:void(0);">dismiss</a>)';

                    var actions = messenger.notificationFlashMessage('reminder', message, {delay: false, flash: false});

                    $('.reminders_dismiss_link[data-id="' + messageObject.id + '"]').bind(
                        'click',
                        {
                            actions: actions,
                            messageObject: messageObject
                        },
                        function (eventObject) {
                            var url = routing.generate('oro_api_post_reminder_shown');
                            var messageObject = eventObject.data.messageObject;
                            $.post(url, { 'ids': [messageObject.id].concat(messageObject.duplicateIds) });
                            eventObject.data.actions.close();
                        }
                    );

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
             * @param {object} messageObject
             * @returns {string}
             */
            reminderTextConstructor: function (messageObject) {
                var message = '';
                try {
                    message = '<i class="icon-bell"></i>';
                    var template = $('.reminder_templates[data-identifier="' + messageObject.templateId + '"]').html();
                    if ($.trim(template) == '') {
                        template = $('.reminder_templates[data-identifier="default"]').html();
                    }
                    message += _.template(template, messageObject);
                } catch (Exception){}

                return message;
            }
        };
    });
