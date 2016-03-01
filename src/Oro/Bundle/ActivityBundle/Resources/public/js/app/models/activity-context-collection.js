define(function(require) {
    'use strict';

    var ActivityContextCollection;
    var ActivityContextModel = require('./activity-context-model');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    ActivityContextCollection = BaseCollection.extend({
        model: ActivityContextModel
    });

    return ActivityContextCollection;
});
