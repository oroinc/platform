/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorModel = require('../app/models/email-editor-model'),
        EmailModel = require('../app/models/email-model');

    return {
        readEmailEditorModel: function (options) {
            var $el = options._sourceElement;
            return new EmailEditorModel({
                appendSignature: options.appendSignature,
                isSignatureEditable: options.isSignatureEditable,
                signature: $el.find('[name$="[signature]"]').val(),
                email: new EmailModel({
                    subject: $el.find('[name$="[subject]"]').val(),
                    body: $el.find('[name$="[body]"]').val(),
                    type: $el.find('[name$="[type]"]').val(),
                    relatedEntityId: options.entityId,
                    parentEmailId: $el.find('[name$="[parentEmailId]"]').val(),
                    cc: options.cc,
                    bcc: options.bcc
                }),
                richEditorEnabled: false,
                bodyFooter: $el.find('[name$="[bodyFooter]"]').val()
            });
        }
    }
});
