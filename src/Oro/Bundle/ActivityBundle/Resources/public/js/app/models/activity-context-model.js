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
        }
    });

    return ActivityContextModel;
});
