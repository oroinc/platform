import BaseModel from 'oroui/js/app/models/base/model';

/**
 * @export oroemail/js/app/models/email-variable-model
 */
const UnreadEmailsStateModel = BaseModel.extend({
    defaults: {
        count: 0,
        ids: []
    },

    /**
     * @inheritdoc
     */
    constructor: function UnreadEmailsStateModel(attrs, options) {
        UnreadEmailsStateModel.__super__.constructor.call(this, attrs, options);
    }
});

export default UnreadEmailsStateModel;
