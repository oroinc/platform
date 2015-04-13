/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentCollection,
        EmailAttachmentModel = require('./email-attachment-model'),
        BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroemail/js/app/models/email-template-collection
     */
    EmailAttachmentCollection = BaseCollection.extend({
        model: EmailAttachmentModel
    });

    return EmailAttachmentCollection;
});
