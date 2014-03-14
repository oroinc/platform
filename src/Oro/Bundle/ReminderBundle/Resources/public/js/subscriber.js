/*global define*/
define(['jquery','orosync/js/sync', 'oroui/js/messenger', 'routing', 'underscore'], function ($, sync, messenger, routing, _) {
    /**
     * @export ororeminder/js/subscriber
     */
    return {
        /**
         * @param {integer} id Current user id
         * @param {string} oldReminders object[] in JSON
         */
        init: function (id, oldReminders) {
            var that = this;

            sync.subscribe('oro/reminder/remind_user_' + id, function (messageParams) {
                messageParams = JSON.parse(messageParams);
                $('.reminders_dismiss_link[data-unique-id="'+messageParams.uniqueId+'"]').click();
                that.showReminders([messageParams]);
            });

            that.showReminders(oldReminders);
        },

        /**
         * @param {array} messageParamsArray
         * @return {object}
         */
        removeDuplicate: function(messageParamsArray){
            var url = routing.generate('oro_reminder_shown');
            var reminderIds = [];
            var uniqueReminders = {};
            messageParamsArray.reduce(function(previouse, current){
                if (previouse[current.uniqueId]) {
                    reminderIds.push(uniqueReminders[current.uniqueId].id);
                }
                previouse[current.uniqueId] = current;

                return previouse;
            }, uniqueReminders);
            if(reminderIds.length>0){
                $.post(url, { 'ids': reminderIds }, function () {});
            }
            return uniqueReminders;
        },

        /**
         * @param {array} messageParamsArray
         */
        showReminders: function (messageParamsArray) {
            $.each(this.removeDuplicate(messageParamsArray), function(key, messageObject){
                var message = this.reminderTextConstructor(messageObject);
                message += '(<a class="reminders_dismiss_link" data-id="' + messageObject.id + '" data-unique-id="'
                    + messageObject.uniqueId + '" href="javascript:void(0);">dismiss</a>)';
                var actions = messenger.notificationFlashMessage('reminder', message, {delay: false, flash: false});
                $('.reminders_dismiss_link[data-id="'+messageObject.id+'"]').click(actions, function(eventObject){
                    var url = routing.generate('oro_reminder_shown');
                    var reminderId = $(this).data('id');
                    eventObject.data.close();
                    $.post(url, { 'ids': [reminderId] }, function () {});
                });
            }.bind(this));

            $('.alert-reminder .close').unbind('click').click(function() {
                $(this).parents('.alert-reminder').find('.reminders_dismiss_link').click();
            });
        },

        /**
         * @param {object} messageObject
         * @returns {string}
         */
        reminderTextConstructor: function(messageObject){

            var message = '<i class="icon-bell"></i>';
            var template = $('.reminder_templates[data-identifier="' + messageObject.templateId + '"]').html();
            if ($.trim(template) == '') {
                template = $('.reminder_templates[data-identifier="default"]').html();
            }
            message += _.template(template, messageObject);
            return message;
        },
        /**
         * Reloads reminders from server
         * It need for actualisation data
         */
        reloadReminders: function(){
            var url = routing.generate('oro_reminder_requested')+'#'+Math.random();
            var that = this;
            require(['text!'+url], function(messageParams){
                messageParams = JSON.parse(messageParams);
                that.showReminders(messageParams);
            });
        }
    };
});
