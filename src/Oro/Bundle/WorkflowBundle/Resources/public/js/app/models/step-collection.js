/* global define */
define(function(require) {
    'use strict';

    var StepCollection,
        BaseCollection = require('oroui/js/app/models/base/collection'),
        StepModel = require('./step-model');

    StepCollection = BaseCollection.extend({
        model: StepModel,
        comparator: 'order'
    });

    return StepCollection;
});
