define(function(require) {
    'use strict';

    var StepCollection;
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var StepModel = require('./step-model');

    StepCollection = BaseCollection.extend({
        model: StepModel,
        comparator: 'order'
    });

    return StepCollection;
});
