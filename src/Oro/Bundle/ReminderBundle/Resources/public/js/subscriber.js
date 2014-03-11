/*global define*/
define(['orosync/js/sync', 'oroui/js/messenger', 'routing'], function (sync, messenger, routing) {
    /**
     * @export ororeminder/js/subscriber
     */
    return {
        /**
         * @param {integer} id Current user id
         * @param {object} Wamp
         * @param {string} oldReminders object[] in JSON
         */
        init: function (id, Wamp, oldReminders) {
            var that = this;
            sync(Wamp);
            sync.subscribe('oro/reminder/remind_user_' + id, function (messageParams) {
                messageParams = JSON.parse(messageParams);
                that.showReminders([messageParams]);
            });
            that.showReminders(JSON.parse(oldReminders));
        },

        /**
         * @param messageParamsArray
         */
        showReminders: function (messageParamsArray) {
            var reminderIds = [];
            for (var i = 0; i < messageParamsArray.length; i++) {
                var messageObject = messageParamsArray[i];
                reminderIds.push(messageObject.reminderId);
                var message = '<a href="' + messageObject.uri + '">' + messageObject.text + '</a>';
                messenger.notificationFlashMessage(false, message, {delay: false, flash: false});
            }
            if (reminderIds.length > 0) {
                this.changeRemindersState(reminderIds);
            }
        },

        /**
         * Change reminder state to sent one
         */
        changeRemindersState: function ($reminderIds) {
            var url = routing.generate('oro_reminder_change_reminder_state', { 'ids': $reminderIds });
            $.post(url, {}, function (result) {
                if (!result.result) {
                    console.log('result.object: \r\n' + result.result);
                }
            });
        }
    };
});
