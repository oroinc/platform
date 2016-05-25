define(function(require) {
    'use strict';

    var EmailAttachmentModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    EmailAttachmentModel = BaseModel.extend({
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
            visible: true
        },

        toggleChecked: function() {
            this.set('checked', !this.get('checked'));
        }
    });

    return EmailAttachmentModel;
});
