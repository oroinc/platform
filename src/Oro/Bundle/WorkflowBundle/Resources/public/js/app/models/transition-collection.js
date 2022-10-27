define(function(require) {
    'use strict';

    const BaseCollection = require('oroui/js/app/models/base/collection');
    const TransitionModel = require('./transition-model');

    const TransitionCollection = BaseCollection.extend({
        model: TransitionModel,

        /**
         * @inheritdoc
         */
        constructor: function TransitionCollection(...args) {
            TransitionCollection.__super__.constructor.apply(this, args);
        }
    });

    return TransitionCollection;
});
