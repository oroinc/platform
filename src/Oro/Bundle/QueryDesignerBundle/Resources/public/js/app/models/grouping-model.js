define(function(require) {
    'use strict';

    const EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    const ColumnModel = EntityFieldModel.extend({
        fieldAttribute: 'name',

        defaults: {
            name: null
        },

        /**
         * @inheritDoc
         */
        constructor: function ColumnModel(...args) {
            ColumnModel.__super__.constructor.apply(this, args);
        }
    });

    return ColumnModel;
});
