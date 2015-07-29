/*jslint nomen: true*/
/*global define*/
define([
    'oroui/js/app/models/base/model'
], function(BaseModel) {
    'use strict';

    var EmailNotificationModel;

    /**
     * @export  oroemail/js/app/models/email-notification-model
     */
    EmailNotificationModel = BaseModel.extend({
        'route': '',
        'id': '',
        'seen': '',
        'subject': '',
        'bodyContent': '',
        'fromName': '',
        'linkFromName': ''
    }   );

    return EmailNotificationModel;
});
