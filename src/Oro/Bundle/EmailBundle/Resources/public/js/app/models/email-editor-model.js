/*global define*/
define(function (require) {
    'use strict';

    var EmailEditorModel,
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-editor-model
     */
    EmailEditorModel = BaseModel.extend({
        defaults: {
            appendSignature: false,
            isSignatureEditable: false,
            signature: undefined,
            email: null,
            richEditorEnabled: false,
            bodyFooter: undefined
        }
    });

    return EmailEditorModel;
});
