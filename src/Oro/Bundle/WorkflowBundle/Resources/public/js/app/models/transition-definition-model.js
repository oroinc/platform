define(function(require) {
    'use strict';

    var TransitionDefinitionModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    TransitionDefinitionModel = BaseModel.extend({
        defaults: {
            name: null,
            preactions: null,
            preconditions: null,
            conditions: null,
            actions: null
        },

        /**
         * @inheritDoc
         */
        constructor: function TransitionDefinitionModel() {
            TransitionDefinitionModel.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            if (this.get('preactions') === null) {
                this.set('preactions', {});
            }
            if (this.get('preconditions') === null) {
                this.set('preconditions', {});
            }
            if (this.get('conditions') === null) {
                this.set('conditions', {});
            }
            if (this.get('actions') === null) {
                this.set('actions', []);
            }
        }
    });

    return TransitionDefinitionModel;
});
