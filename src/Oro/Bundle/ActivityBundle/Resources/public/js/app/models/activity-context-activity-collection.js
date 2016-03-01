define(function(require) {
    'use strict';

    var ActivityContextActivityCollection;
    var ActivityContextActivityModel = require('./activity-context-activity-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    /**
     * @export  oroactivity/js/app/models/activity-context-collection
     */
    ActivityContextActivityCollection = BaseCollection.extend({
        route: null,
        routeId: null,
        includeNonEntity: false,
        includeSystemTemplates: true,
        url: null,
        model: ActivityContextActivityModel
    });

    return ActivityContextActivityCollection;
});
