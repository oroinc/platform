import EmailAttachmentModel from './email-attachment-model';
import BaseCollection from 'oroui/js/app/models/base/collection';

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

export default EmailAttachmentCollection;
