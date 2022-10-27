define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    const EmailAttachmentModel = BaseModel.extend({
        defaults: {
            id: null,
            type: '',
            title: '',
            fileName: '',
            fileSize: '',
            modified: '',
            preview: '',
            icon: '',
            checked: false, // whether file is checked for attaching to an email
            visible: true,
            errors: []
        },

        /**
         * @inheritdoc
         */
        constructor: function EmailAttachmentModel(...args) {
            EmailAttachmentModel.__super__.constructor.apply(this, args);
        },

        toggleChecked: function() {
            this.set('checked', !this.get('checked'));
        }
    });

    return EmailAttachmentModel;
});
