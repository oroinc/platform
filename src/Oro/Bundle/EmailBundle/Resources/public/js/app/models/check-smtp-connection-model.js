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
        }
    });
    return CheckSmtpConnectionModel;
});
