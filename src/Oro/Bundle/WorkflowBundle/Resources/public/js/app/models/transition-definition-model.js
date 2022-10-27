define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const TransitionDefinitionModel = BaseModel.extend({
        defaults: {
            name: null,
            preactions: null,
            preconditions: null,
            conditions: null,
            actions: null
        },

        /**
         * @inheritdoc
         */
        constructor: function TransitionDefinitionModel(attrs, options) {
            TransitionDefinitionModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @inheritdoc
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
