/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentModel,
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    EmailAttachmentModel = BaseModel.extend({
        defaults: {
            id: '',
            fileName: ''
        }
    });

    return EmailAttachmentModel;
});
