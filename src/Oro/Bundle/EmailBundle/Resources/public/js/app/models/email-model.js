/*global define*/
define(function (require) {
    'use strict';

    var EmailModel,
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-model
     */
    EmailModel = BaseModel.extend({
        defaults: {
            parentEmailId: undefined,
            entityId: undefined,
            cc: undefined,
            bcc: undefined
        }
    });

    return EmailModel;
});
