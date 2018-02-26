define(function(require) {
    'use strict';

    var ActivityContextModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroactivity/js/app/models/activity-context-model
     */
    ActivityContextModel = BaseModel.extend({
        defaults: {
            label: '',
            first: '',
            className: '',
            gridName: ''
        },

        /**
         * @inheritDoc
         */
        constructor: function ActivityContextModel() {
            ActivityContextModel.__super__.constructor.apply(this, arguments);
        }
    });

    return ActivityContextModel;
});
