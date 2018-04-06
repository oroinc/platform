define(function(require) {
    'use strict';

    var StepCollection;
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var StepModel = require('./step-model');

    StepCollection = BaseCollection.extend({
        model: StepModel,

        comparator: 'order',

        /**
         * @inheritDoc
         */
        constructor: function StepCollection() {
            StepCollection.__super__.constructor.apply(this, arguments);
        }
    });

    return StepCollection;
});
