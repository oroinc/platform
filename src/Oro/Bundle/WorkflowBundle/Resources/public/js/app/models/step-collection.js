define(function(require) {
    'use strict';

    const BaseCollection = require('oroui/js/app/models/base/collection');
    const StepModel = require('./step-model');

    const StepCollection = BaseCollection.extend({
        model: StepModel,

        comparator: 'order',

        /**
         * @inheritdoc
         */
        constructor: function StepCollection(...args) {
            StepCollection.__super__.constructor.apply(this, args);
        }
    });

    return StepCollection;
});
