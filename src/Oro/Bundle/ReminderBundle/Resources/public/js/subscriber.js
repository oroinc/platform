/*global define*/
define(['orosync/js/sync', 'oroui/js/messenger'], function(sync, messenger){
    /**
     * @export ororeminder/js/subscriber
     */
    return {
        /**
         * @param {integer} id Current user id
         * @param {object} Wamp
         */
        init: function(id, Wamp){
            sync(Wamp);
            sync.subscribe('oro/reminder/remind_user_' + id, function (messageObject) {
                messageObject = JSON.parse(messageObject);
                var message =  '<a href="' + messageObject.uri + '">' + messageObject.text + '</a>';
                messenger.notificationFlashMessage(false, message, {delay: false, flash: false});
            });
        }
    };
});
