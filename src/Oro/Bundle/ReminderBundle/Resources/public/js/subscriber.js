/*global define*/
define(['orosync/js/sync', 'oroui/js/messenger', 'routing'], function (sync, messenger, routing) {
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
                that.showReminders([messageParams]);
            });
            that.showReminders(oldReminders);
        },

        /**
         * @param {array} messageParamsArray
         */
        showReminders: function (messageParamsArray) {
            var reminderIds = [];
            for (var i = 0; i < messageParamsArray.length; i++) {
                var messageObject = messageParamsArray[i];
                reminderIds.push(messageObject.id);

                var message = messageObject.url != ''
                    ? '<a href="' + messageObject.url + '">' + messageObject.text + '</a>'
                    : '<span>' + messageObject.text + '</span>';
                message = '<i class="icon-bell"></i>'+message;

                messenger.notificationFlashMessage('reminder', message, {delay: false, flash: false});
            }
            if (reminderIds.length > 0) {
                this.changeRemindersState(reminderIds);
            }
        },

        /**
         * Change reminder state to sent one
         * @param {array} $reminderIds
         */
        changeRemindersState: function ($reminderIds) {
            var url = routing.generate('oro_reminder_change_reminder_state');
            $.post(url, { 'ids': $reminderIds }, function () {});
        }
    };
});
