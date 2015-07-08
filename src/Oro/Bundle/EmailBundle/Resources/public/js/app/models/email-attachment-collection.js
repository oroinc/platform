define(function(require) {
    'use strict';

    var EmailAttachmentCollection;
    var EmailAttachmentModel = require('./email-attachment-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailAttachmentCollection = BaseCollection.extend({
        model: EmailAttachmentModel
    });

    return EmailAttachmentCollection;
});
