define(function(require) {
    'use strict';

    const EmailAttachmentModel = require('./email-attachment-model');
    const BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    const EmailAttachmentCollection = BaseCollection.extend({
        model: EmailAttachmentModel,

        /**
         * @inheritdoc
         */
        constructor: function EmailAttachmentCollection(...args) {
            EmailAttachmentCollection.__super__.constructor.apply(this, args);
        }
    });

    return EmailAttachmentCollection;
});
