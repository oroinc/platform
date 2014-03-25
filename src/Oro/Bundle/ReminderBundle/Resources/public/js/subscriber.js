/*global define*/
define(
    ['jquery', 'orosync/js/sync', 'oroui/js/messenger', 'routing', 'underscore', 'oronavigation/js/navigation'],
    function ($, sync, messenger, routing, _, Navigation) {
        /**
         * @export ororeminder/js/subscriber
         */
        return {
            deletedList: {},

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
             * @param {Array} messageParamsArray
             * @return {Array}
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
             * @param {Array} messageParamsArray
             * @return {Array}
             */
            removePhantomReminders: function(messageParamsArray) {
                var result = [];
                var currentDate = new Date();
                var currentTime = currentDate.getTime();
                _.each(messageParamsArray, function(element){
                    if (!this.deletedList[element.id]) {
                        result.push(element);
                    } else if ($.type(this.deletedList[element.id]) == 'date' && this.deletedList[element.id].getTime) {
                        var dismissTime = this.deletedList[element.id].getTime();
                        var timeDifference = currentTime - dismissTime;

                        // if dismissed more then one minutes ago show it
                        if (timeDifference > 60000) {
                            result.push(element);
                        }
                    }
                }, this);

                return result;
            },

            /**
             * @param {Array} messageParamsArray
             */
            showReminders: function (messageParamsArray) {
                var self = this;
                messageParamsArray = this.removeDuplicate(this.removePhantomReminders(messageParamsArray));
                $('.alert-reminder').remove();
                _.each(messageParamsArray, function (messageObject) {
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

                            var deletingList = [messageObject.id].concat(messageObject.duplicateIds);

                            $.post(url, { 'ids': deletingList });

                            deletingList.reduce(function(previousState, currentElement) {
                                previousState[currentElement] = new Date();
                                return previousState;
                            }, self.deletedList);

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
