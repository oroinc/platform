define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

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
    return CheckSmtpConnectionModel;
});
