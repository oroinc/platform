define(function(require) {
    'use strict';

    var CheckSmtpConnectionModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    CheckSmtpConnectionModel = BaseModel.extend({
        defaults: {
            host: '',
            port: null,
            encryption: null,
            username: '',
            password: ''
        },

        /**
         * @inheritDoc
         */
        constructor: function CheckSmtpConnectionModel() {
            CheckSmtpConnectionModel.__super__.constructor.apply(this, arguments);
        }
    });
    return CheckSmtpConnectionModel;
});
