define(function(require) {
    'use strict';

    const ActivityContextModel = require('./activity-context-model');
    const BaseCollection = require('oroui/js/app/models/base/collection');

    const ActivityContextCollection = BaseCollection.extend({
        model: ActivityContextModel,

        /**
         * @inheritdoc
         */
        constructor: function ActivityContextCollection(...args) {
            ActivityContextCollection.__super__.constructor.apply(this, args);
        }
    });

    return ActivityContextCollection;
});
