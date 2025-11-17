import BaseModel from 'oroui/js/app/models/base/model';

const CheckSmtpConnectionModel = BaseModel.extend({
    defaults: {
        host: '',
        port: null,
        encryption: null,
        username: '',
        password: ''
    },

    /**
     * @inheritdoc
     */
    constructor: function CheckSmtpConnectionModel(...args) {
        CheckSmtpConnectionModel.__super__.constructor.apply(this, args);
    }
});
export default CheckSmtpConnectionModel;
