define(function(require) {
    'use strict';

    var EmailContextActivityModel;
    var routing = require('routing');
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-context-activity-model
     */
    EmailContextActivityModel = BaseModel.extend({
        defaults: {
            entity: '',
            className: '',
            id: '',
            name: ''
        },
        url: function() {
            var param = {
                activity: 'emails',
                id: this.get('entityId'),
                entity:  this.get('targetClassName'),
                entityId: this.get('targetId')
            };

            return routing.generate('oro_api_delete_activity_relation', param);
        }
    });

    return EmailContextActivityModel;
});
