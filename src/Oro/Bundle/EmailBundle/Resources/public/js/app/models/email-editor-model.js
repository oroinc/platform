import BaseModel from 'oroui/js/app/models/base/model';

/**
 * @export  oroemail/js/app/models/email-editor-model
 */
const EmailEditorModel = BaseModel.extend({
    defaults: {
        appendSignature: false,
        isSignatureEditable: false,
        signature: undefined,
        email: null,
        bodyFooter: undefined
    },

    /**
     * @inheritdoc
     */
    constructor: function EmailEditorModel(...args) {
        EmailEditorModel.__super__.constructor.apply(this, args);
    }
});

export default EmailEditorModel;
