/*global define*/
define(function (require) {
    'use strict';

    var EmailContextModel,
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    EmailContextModel = BaseModel.extend({
        defaults: {
            id: '',
            label: '',
            first: ''
        }
    });

    return EmailContextModel;
});