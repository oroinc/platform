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
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailTemplateModel() {
            EmailTemplateModel.__super__.constructor.apply(this, arguments);
        }
    });

    return EmailTemplateModel;
});
