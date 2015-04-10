/*global define*/
define(function (require) {
    'use strict';

    var EmailContextModel,
        routing = require('routing'),
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-context-model
     */
    EmailContextModel = BaseModel.extend({
        defaults: {
            entity: '',
            className: '',
            id: '',
            name: ''
        },
        url:function() {
            var param = {
                'entityId': this.get('entityId'),
                'targetClassName':  this.get('className'),
                'targetId': this.get('id')
            };

            return routing.generate('oro_api_delete_email_association', param)
        }
    });

    return EmailContextModel;
});
