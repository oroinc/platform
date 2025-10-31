import BaseModel from 'oroui/js/app/models/base/model';

/**
 * @export  oroemail/js/app/models/email-template-model
 */
const EmailTemplateModel = BaseModel.extend({
    defaults: {
        entity: '',
        id: '',
        name: ''
    },

    /**
     * @inheritdoc
     */
    constructor: function EmailTemplateModel(...args) {
        EmailTemplateModel.__super__.constructor.apply(this, args);
    }
});

export default EmailTemplateModel;
