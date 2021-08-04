define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-model
     */
    const EmailModel = BaseModel.extend({
        defaults: {
            parentEmailId: undefined,
            entityId: undefined,
            cc: undefined,
            bcc: undefined
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailModel(...args) {
            EmailModel.__super__.constructor.apply(this, args);
        }
    });

    return EmailModel;
});
