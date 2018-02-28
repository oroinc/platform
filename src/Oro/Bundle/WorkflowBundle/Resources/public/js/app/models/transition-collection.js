define(function(require) {
    'use strict';

    var TransitionCollection;
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var TransitionModel = require('./transition-model');

    TransitionCollection = BaseCollection.extend({
        model: TransitionModel,

        /**
         * @inheritDoc
         */
        constructor: function TransitionCollection() {
            TransitionCollection.__super__.constructor.apply(this, arguments);
        }
    });

    return TransitionCollection;
});
