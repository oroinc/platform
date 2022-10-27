define(function(require) {
    'use strict';

    const BaseCollection = require('oroui/js/app/models/base/collection');
    const TransitionDefinitionModel = require('./transition-definition-model');

    const TransitionDefinitionCollection = BaseCollection.extend({
        model: TransitionDefinitionModel,

        /**
         * @inheritdoc
         */
        constructor: function TransitionDefinitionCollection(...args) {
            TransitionDefinitionCollection.__super__.constructor.apply(this, args);
        }
    });

    return TransitionDefinitionCollection;
});
