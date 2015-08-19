define(function(require) {
    'use strict';

    var EmailContextModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-context-model
     */
    EmailContextModel = BaseModel.extend({
        defaults: {
            label: '',
            first: '',
            className: '',
            gridName: ''
        }
    });

    return EmailContextModel;
});
