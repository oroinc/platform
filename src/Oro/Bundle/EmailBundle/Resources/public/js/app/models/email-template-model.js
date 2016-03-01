define(function(require) {
    'use strict';

    var EmailTemplateModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    EmailTemplateModel = BaseModel.extend({
        defaults: {
            entity: '',
            id: '',
            name: ''
        }
    });

    return EmailTemplateModel;
});
