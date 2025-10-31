import BaseModel from 'oroui/js/app/models/base/model';

const CheckConnectionModel = BaseModel.extend({
    defaults: {
        imap: {},
        smtp: {}
    },

    /**
     * @inheritdoc
     */
    constructor: function CheckConnectionModel(...args) {
        CheckConnectionModel.__super__.constructor.apply(this, args);
    }
});
export default CheckConnectionModel;
