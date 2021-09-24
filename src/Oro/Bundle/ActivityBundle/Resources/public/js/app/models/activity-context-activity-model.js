define(function(require) {
    'use strict';

    const routing = require('routing');
    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroactivity/js/app/models/activity-context-activity-model
     */
    const ActivityContextActivityModel = BaseModel.extend({
        defaults: {
            entity: '',
            className: '',
            id: '',
            name: ''
        },

        /**
         * @inheritdoc
         */
        constructor: function ActivityContextActivityModel(...args) {
            ActivityContextActivityModel.__super__.constructor.apply(this, args);
        },

        url: function() {
            const param = {
                activity: this.get('activityClassAlias'),
                id: this.get('entityId'),
                entity: this.get('targetClassName'),
                entityId: this.get('targetId')
            };

            return routing.generate('oro_api_delete_activity_relation', param);
        }
    });

    return ActivityContextActivityModel;
});
