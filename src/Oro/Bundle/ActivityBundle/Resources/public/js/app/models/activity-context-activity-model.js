define(function(require) {
    'use strict';

    var ActivityContextActivityModel;
    var routing = require('routing');
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroactivity/js/app/models/activity-context-activity-model
     */
    ActivityContextActivityModel = BaseModel.extend({
        defaults: {
            entity: '',
            className: '',
            id: '',
            name: ''
        },
        url: function() {
            var param = {
                activity: this.get('activityClassAlias'),
                id: this.get('entityId'),
                entity:  this.get('targetClassName'),
                entityId: this.get('targetId')
            };

            return routing.generate('oro_api_delete_activity_relation', param);
        }
    });

    return ActivityContextActivityModel;
});
