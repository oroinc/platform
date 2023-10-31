define(function(require) {
    'use strict';

    const ActivityContextActivityModel = require('./activity-context-activity-model');
    const BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroactivity/js/app/models/activity-context-collection
     */
    const ActivityContextActivityCollection = BaseCollection.extend({
        route: null,

        routeId: null,

        includeNonEntity: false,

        includeSystemTemplates: true,

        url: null,

        model: function(attributes, options) {
            if (typeof options.deleteRoute !== 'undefined') {
                options.route = options.deleteRoute;
                delete options.deleteRoute;
            }
            return new ActivityContextActivityModel(attributes, options);
        },

        /**
         * @inheritdoc
         */
        constructor: function ActivityContextActivityCollection(...args) {
            ActivityContextActivityCollection.__super__.constructor.apply(this, args);
        }
    });

    return ActivityContextActivityCollection;
});
